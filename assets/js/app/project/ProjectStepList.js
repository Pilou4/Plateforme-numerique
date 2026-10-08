import { apiDelete, apiGet, apiPatch, apiPost, apiPut } from '../../fonctions/api.js';
import { formatDuration, joinDuration, splitDuration } from '../../fonctions/duration.js';
import { getModal, openModalFromTemplate } from '../../composants/Modal.js';
import { showNotice } from '../../composants/Notice.js';
import { matchesSearch, PAGE_SEARCH_EVENT } from '../../composants/PageSearch.js';

const FILTER_ALL = 'all';
const STATUS_TODO = 'todo';
const STATUS_IN_PROGRESS = 'in_progress';
const STATUS_DONE = 'done';
const SAVED_CLASS = 'steps-table__row--saved';
const DRAGGING_CLASS = 'steps-table__row--dragging';
const ADD_FORM_TEMPLATE_ID = 'project-step-add';
const COLUMN_COUNT = 8;
const SORT_MANUAL = 'position';
const SORT_ASCENDING = 'ascending';
const SORT_DESCENDING = 'descending';
// Rang utilisé pour trier les priorités (la plus importante d'abord) et les statuts
const PRIORITY_RANK = { high: 0, normal: 1, low: 2 };
const STATUS_RANK = { todo: 0, in_progress: 1, done: 2 };

/**
 * Tableau des tâches du projet et de leurs sous-tâches :
 * chargement, ajout (dans la modal), modification directe dans les cellules,
 * suppression, filtres, recherche, tri par colonne, dépliage des sous-tâches et glisser-déposer.
 *
 * Une ligne de tâche porte data-step, une ligne de sous-tâche porte data-sub-step
 * (et data-parent-id = l'identifiant de son tâche).
 */
export class ProjectStepList {
    constructor(root) {
        this.root = root;
        this.apiUrl = root.dataset.apiUrl;

        this.list = root.querySelector('[data-list]');
        this.emptyMessage = root.querySelector('[data-empty]');
        this.stepTemplate = root.querySelector('[data-template="step"]');
        this.subStepTemplate = root.querySelector('[data-template="sub-step"]');
        this.filterButtons = root.querySelectorAll('[data-filter]');
        this.sortHeads = root.querySelectorAll('[data-sort-head]');
        this.progress = root.querySelector('[data-progress]');
        this.progressBar = root.querySelector('[data-progress-bar]');

        this.steps = [];
        this.filter = FILTER_ALL;
        // Tri affiché (non enregistré) : "position" = ordre manuel du glisser-déposer
        this.sortKey = SORT_MANUAL;
        this.sortDirection = SORT_ASCENDING;
        // Recherche de l'en-tête de la page (texte normalisé, '' = pas de recherche)
        this.searchQuery = '';
        // Identifiants des tâches dont les sous-tâches sont dépliées
        this.expandedStepIds = new Set();
        this.draggedRow = null;
        this.placeholder = this.createPlaceholder();

        // Les méthodes branchées sur des événements gardent "this" = l'instance
        this.handleClick = this.handleClick.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handleAddSubmit = this.handleAddSubmit.bind(this);
        this.handleFocusIn = this.handleFocusIn.bind(this);
        this.handleFocusOut = this.handleFocusOut.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleSelectChange = this.handleSelectChange.bind(this);
        this.handlePointerDown = this.handlePointerDown.bind(this);
        this.handlePointerUp = this.handlePointerUp.bind(this);
        this.handleDragStart = this.handleDragStart.bind(this);
        this.handleDragOver = this.handleDragOver.bind(this);
        this.handleDrop = this.handleDrop.bind(this);
        this.handleDragEnd = this.handleDragEnd.bind(this);
        this.hideDraggedRows = this.hideDraggedRows.bind(this);
    }

    async init() {
        this.root.addEventListener('click', this.handleClick);
        // La barre de recherche est dans l'en-tête de la page, hors de this.root
        document.addEventListener(PAGE_SEARCH_EVENT, this.handleSearch);
        // Le formulaire d'ajout est dans la modal, en dehors de la page : écoute sur document
        document.addEventListener('submit', this.handleAddSubmit);

        // Modification directe dans les cellules
        this.list.addEventListener('focusin', this.handleFocusIn);
        this.list.addEventListener('focusout', this.handleFocusOut);
        this.list.addEventListener('keydown', this.handleKeyDown);
        this.list.addEventListener('change', this.handleSelectChange);

        // Glisser-déposer
        this.list.addEventListener('pointerdown', this.handlePointerDown);
        this.list.addEventListener('pointerup', this.handlePointerUp);
        this.list.addEventListener('dragstart', this.handleDragStart);
        this.list.addEventListener('dragover', this.handleDragOver);
        this.list.addEventListener('drop', this.handleDrop);
        this.list.addEventListener('dragend', this.handleDragEnd);

        await this.load();
    }

    async load() {
        // Pendant le chargement, le message vide du tableau sert d'indication
        this.emptyMessage.hidden = false;
        this.emptyMessage.textContent = 'Chargement des tâches…';

        try {
            this.steps = await apiGet(this.apiUrl);
        } catch (error) {
            showNotice('error', `Impossible de charger les tâches : ${error.message}`);
        }

        this.render();
    }

    /* ------------------------------------------------------------------
       Affichage
       ------------------------------------------------------------------ */

    render() {
        this.renderStats();
        this.renderFilters();
        this.renderSortHeads();
        this.renderList();
    }

    renderStats() {
        const total = this.steps.length;
        const done = this.countByStatus(STATUS_DONE);
        const totalTime = this.steps.reduce(addTotalTimeSpent, 0);
        const percent = total > 0 ? Math.round((done / total) * 100) : 0;

        this.setStat('done', `${done} / ${total}`);
        this.setStat('in-progress', String(this.countByStatus(STATUS_IN_PROGRESS)));
        this.setStat('todo', String(this.countByStatus(STATUS_TODO)));
        this.setStat('time', totalTime > 0 ? formatDuration(totalTime) : '0 min');

        this.progressBar.style.width = `${percent}%`;
        this.progress.setAttribute('aria-valuenow', String(percent));
    }

    renderFilters() {
        for (const button of this.filterButtons) {
            const isActive = button.dataset.filter === this.filter;
            button.setAttribute('aria-pressed', String(isActive));
            button.classList.toggle('steps__filter--active', isActive);
        }
    }

    renderSortHeads() {
        for (const head of this.sortHeads) {
            const isActive = head.dataset.sortHead === this.sortKey;
            head.setAttribute('aria-sort', isActive ? this.sortDirection : 'none');
            head.classList.toggle('steps-table__head--sorted', isActive && this.sortKey !== SORT_MANUAL);
        }
    }

    renderList() {
        const visibleSteps = this.getVisibleSteps();
        const rows = [];

        for (const step of visibleSteps) {
            rows.push(this.createStepRow(step));
            rows.push(...this.sortItems(this.getVisibleSubSteps(step)).map((subStep) => this.createSubStepRow(step, subStep)));
        }

        this.list.replaceChildren(...rows);
        // Le glisser-déposer n'est possible que sur la liste complète, dans l'ordre manuel, sans recherche
        this.list.classList.toggle('steps-table__body--sortable', this.isReorderable());

        this.emptyMessage.hidden = visibleSteps.length > 0;
        this.emptyMessage.textContent = this.getEmptyMessage();
    }

    createStepRow(step) {
        const row = this.stepTemplate.content.firstElementChild.cloneNode(true);
        row.dataset.id = String(step.id);
        this.fillStepRow(row, step);

        return row;
    }

    createSubStepRow(step, subStep) {
        const row = this.subStepTemplate.content.firstElementChild.cloneNode(true);
        row.dataset.id = String(subStep.id);
        row.dataset.parentId = String(step.id);
        this.fillSubStepRow(row, step, subStep);

        return row;
    }

    /**
     * Met les valeurs de la tâche dans sa ligne.
     */
    fillStepRow(row, step) {
        const hasSubSteps = step.subSteps.length > 0;
        const isExpanded = this.expandedStepIds.has(step.id);
        const toggle = row.querySelector('[data-action="toggle"]');
        const count = row.querySelector('[data-field="count"]');

        this.fillCommonFields(row, step);
        row.querySelector('[data-field="number"]').textContent = String(this.getStepNumber(step));
        row.classList.toggle('steps-table__row--expanded', isExpanded);

        // Flèche et compteur uniquement si la tâche a des sous-tâches
        toggle.hidden = !hasSubSteps;
        toggle.setAttribute('aria-expanded', String(isExpanded));
        toggle.setAttribute('aria-label', `${isExpanded ? 'Masquer' : 'Afficher'} les sous-tâches de « ${step.title} »`);
        count.hidden = !hasSubSteps;
        count.textContent = `${step.subSteps.filter(isDone).length}/${step.subSteps.length}`;

        // Avec des sous-tâches, le temps est la somme automatique (non modifiable)
        row.querySelector('[data-time-inputs]').hidden = hasSubSteps;
        const total = row.querySelector('[data-time-total]');
        total.hidden = !hasSubSteps;
        total.textContent = formatDuration(step.totalTimeSpent);

        row.querySelector('[data-action="add-sub-step"]').setAttribute('aria-label', `Ajouter une sous-tâche à « ${step.title} »`);
        row.querySelector('[data-action="delete"]').setAttribute('aria-label', `Supprimer la tâche « ${step.title} »`);
    }

    /**
     * Met les valeurs de la sous-tâche dans sa ligne.
     */
    fillSubStepRow(row, step, subStep) {
        const subStepIndex = step.subSteps.indexOf(subStep) + 1;

        this.fillCommonFields(row, subStep);
        row.querySelector('[data-field="number"]').textContent = `${this.getStepNumber(step)}.${subStepIndex}`;
        row.querySelector('[data-action="delete"]').setAttribute('aria-label', `Supprimer la sous-tâche « ${subStep.title} »`);
    }

    /**
     * Champs identiques pour une tâche et une sous-tâche.
     */
    fillCommonFields(row, item) {
        const { hours, minutes } = splitDuration(item.timeSpent);
        const title = this.getEditField(row, 'title');
        const description = this.getEditField(row, 'description');

        row.classList.toggle('steps-table__row--done', item.status === STATUS_DONE);

        setFieldValue(title, item.title);
        setFieldValue(description, item.description ?? '');
        // Texte complet au survol, utile quand il est coupé
        title.title = item.title;
        description.title = item.description ?? '';

        setFieldValue(this.getEditField(row, 'priority'), item.priority);
        setFieldValue(this.getEditField(row, 'status'), item.status);
        setFieldValue(this.getEditField(row, 'hours'), String(hours));
        setFieldValue(this.getEditField(row, 'minutes'), String(minutes));
        this.updateSelectColors(row);
    }

    updateSelectColors(row) {
        const priority = this.getEditField(row, 'priority');
        const status = this.getEditField(row, 'status');

        priority.className = `steps-table__select steps-table__select--priority-${priority.value}`;
        status.className = `steps-table__select steps-table__select--status-${status.value}`;
    }

    /**
     * Met à jour, sans reconstruire le tableau, la ligne d'une tâche et celles de ses sous-tâches.
     * Garde le focus dans la cellule en cours de saisie.
     */
    refreshStepRows(step) {
        const stepRow = this.findStepRow(step.id);

        if (stepRow) {
            this.fillStepRow(stepRow, step);
        }

        for (const subStep of step.subSteps) {
            const subStepRow = this.findSubStepRow(subStep.id);

            if (subStepRow) {
                this.fillSubStepRow(subStepRow, step, subStep);
            }
        }
    }

    /* ------------------------------------------------------------------
       Clics (délégation : un seul écouteur pour toute la page)
       ------------------------------------------------------------------ */

    handleClick(event) {
        const filterButton = event.target.closest('[data-filter]');

        if (filterButton) {
            this.setFilter(filterButton.dataset.filter);

            return;
        }

        const sortButton = event.target.closest('[data-sort]');

        if (sortButton) {
            this.setSort(sortButton.dataset.sort);

            return;
        }

        const actionButton = event.target.closest('[data-action]');

        if (!actionButton || !this.list.contains(actionButton)) {
            return;
        }

        const target = this.resolveRow(actionButton);

        switch (actionButton.dataset.action) {
            case 'toggle':
                this.toggleSubSteps(target.step);
                break;
            case 'add-sub-step':
                this.openSubStepForm(target.step);
                break;
            case 'delete':
                if (target.subStep) {
                    this.deleteSubStep(target.step, target.subStep);
                } else {
                    this.deleteStep(target.step);
                }
                break;
        }
    }

    getEmptyMessage() {
        if (this.steps.length === 0) {
            return 'Aucune tâche pour l\'instant. Ajoute la première avec le bouton « Ajouter une tâche ».';
        }

        return this.searchQuery !== '' ? 'Aucune tâche ne correspond à la recherche.' : 'Aucune tâche ne correspond à ce filtre.';
    }

    handleSearch(event) {
        this.searchQuery = event.detail.normalizedQuery;
        this.renderList();
    }

    setFilter(filter) {
        this.filter = filter;
        this.render();
    }

    /**
     * Premier clic sur une colonne : tri croissant ; clic suivant : décroissant, et ainsi de suite.
     * La colonne « # » revient toujours à l'ordre manuel.
     */
    setSort(key) {
        if (key === SORT_MANUAL) {
            this.sortDirection = SORT_ASCENDING;
        } else if (key === this.sortKey) {
            this.sortDirection = this.sortDirection === SORT_ASCENDING ? SORT_DESCENDING : SORT_ASCENDING;
        } else {
            this.sortDirection = SORT_ASCENDING;
        }

        this.sortKey = key;
        this.renderSortHeads();
        this.renderList();
        if (key !== SORT_MANUAL) {
            showNotice('info', 'Tableau trié : le glisser-déposer revient avec la colonne « # ».');
        }
    }

    toggleSubSteps(step) {
        if (this.expandedStepIds.has(step.id)) {
            this.expandedStepIds.delete(step.id);
        } else {
            this.expandedStepIds.add(step.id);
        }

        this.renderList();
        this.findStepRow(step.id)?.querySelector('[data-action="toggle"]').focus();
    }

    /* ------------------------------------------------------------------
       Ajout (formulaire dans la modal) : tâche ou sous-tâche
       ------------------------------------------------------------------ */

    openSubStepForm(step) {
        const modal = openModalFromTemplate(ADD_FORM_TEMPLATE_ID, {
            title: `Nouvelle sous-tâche · ${step.title}`,
        });

        // Le même formulaire sert pour les sous-tâches : on lui indique la tâche parente
        modal?.getBody().querySelector('[data-step-add-form]')?.setAttribute('data-parent-id', String(step.id));
    }

    async handleAddSubmit(event) {
        const form = event.target;

        if (!form.matches('[data-step-add-form]')) {
            return;
        }

        event.preventDefault();

        const data = {
            title: form.elements.title.value.trim(),
            description: form.elements.description.value.trim(),
            priority: form.elements.priority.value,
        };
        const parentId = form.dataset.parentId ? Number(form.dataset.parentId) : null;
        const error = form.querySelector('[data-form-error]');
        const submitButton = form.querySelector('[type="submit"]');

        if (data.title === '') {
            error.textContent = 'Le titre est obligatoire.';
            form.elements.title.focus();

            return;
        }

        error.textContent = '';
        submitButton.disabled = true;

        try {
            if (parentId) {
                const updatedStep = await apiPost(this.getSubStepCreateUrl(parentId), data);
                this.replaceStep(updatedStep);
                this.expandedStepIds.add(parentId);
                showNotice('success', 'Sous-tâche ajoutée.');
            } else {
                const createdStep = await apiPost(this.apiUrl, data);
                this.steps.push(createdStep);
                showNotice('success', 'Tâche ajoutée.');
            }

            getModal().close();
            this.render();
        } catch (apiError) {
            error.textContent = apiError.message;
            submitButton.disabled = false;
            form.elements.title.focus();
        }
    }

    /* ------------------------------------------------------------------
       Modification directe dans les cellules
       - champs texte et temps : enregistrés en quittant le champ ou avec Entrée
       - Échap annule la saisie en cours
       - listes (priorité, statut) : enregistrées dès le changement
       ------------------------------------------------------------------ */

    handleFocusIn(event) {
        const field = event.target.closest('input[data-edit]');

        if (field) {
            // Valeur de départ, pour savoir s'il y a eu un changement et pouvoir annuler
            field.dataset.initialValue = field.value;
        }
    }

    handleKeyDown(event) {
        const field = event.target.closest('input[data-edit]');

        if (!field) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            field.blur();
        } else if (event.key === 'Escape') {
            field.value = field.dataset.initialValue ?? field.value;
            field.blur();
        }
    }

    handleFocusOut(event) {
        const field = event.target.closest('input[data-edit]');

        if (!field || field.value === field.dataset.initialValue) {
            return;
        }

        const target = this.resolveRow(field);

        if (!target.step) {
            return;
        }

        switch (field.dataset.edit) {
            case 'title':
                this.saveTitle(target, field);
                break;
            case 'description':
                this.save(target, { description: field.value.trim() });
                break;
            case 'hours':
            case 'minutes':
                this.saveTime(target);
                break;
        }
    }

    handleSelectChange(event) {
        const select = event.target.closest('select[data-edit]');

        if (!select) {
            return;
        }

        const target = this.resolveRow(select);

        this.updateSelectColors(target.row);
        this.save(target, { [select.dataset.edit]: select.value });
    }

    saveTitle(target, field) {
        const title = field.value.trim();

        if (title === '') {
            field.value = target.item.title;
            showNotice('warning', 'Le titre ne peut pas être vide.');

            return;
        }

        this.save(target, { title });
    }

    saveTime(target) {
        const hours = readPositiveInteger(this.getEditField(target.row, 'hours'));
        const minutes = readPositiveInteger(this.getEditField(target.row, 'minutes'));
        const timeSpent = joinDuration(hours, minutes);

        if (timeSpent === target.item.timeSpent) {
            return;
        }

        this.save(target, { timeSpent });
    }

    /**
     * Enregistre une modification d'tâche ou de sous-tâche.
     * L'API renvoie toujours la tâche complète (statut et temps total recalculés).
     */
    async save(target, data) {
        const url = target.subStep ? this.getSubStepUrl(target.step.id, target.subStep.id) : this.getStepUrl(target.step.id);

        try {
            const updatedStep = await apiPatch(url, data);
            const hasStatusChanged = updatedStep.status !== target.step.status;

            this.replaceStep(updatedStep);
            this.renderStats();

            // Avec un filtre, la tâche peut ne plus correspondre ; avec un tri, elle peut changer de place :
            // on réaffiche la liste
            if ((hasStatusChanged && this.filter !== FILTER_ALL) || this.hasSortPositionChanged(target.step, updatedStep)) {
                this.renderList();
            } else {
                this.refreshStepRows(updatedStep);
                this.flashRow(target.subStep ? this.findSubStepRow(target.subStep.id) : this.findStepRow(updatedStep.id));
            }

            showNotice('success', 'Modification enregistrée.');
        } catch (error) {
            // Échec : les lignes reprennent les valeurs enregistrées
            this.refreshStepRows(target.step);
            showNotice('error', `Modification non enregistrée : ${error.message}`);
        }
    }

    flashRow(row) {
        if (!row) {
            return;
        }

        row.classList.remove(SAVED_CLASS);
        // Force le navigateur à relancer l'animation
        void row.offsetWidth;
        row.classList.add(SAVED_CLASS);
        row.addEventListener('animationend', removeSavedClass, { once: true });
    }

    /* ------------------------------------------------------------------
       Suppression
       ------------------------------------------------------------------ */

    async deleteStep(step) {
        const subStepCount = step.subSteps.length;
        let subStepsWarning = '';

        if (subStepCount === 1) {
            subStepsWarning = ' Sa sous-tâche sera aussi supprimée.';
        } else if (subStepCount > 1) {
            subStepsWarning = ` Ses ${subStepCount} sous-tâches seront aussi supprimées.`;
        }

        if (!window.confirm(`Supprimer la tâche « ${step.title} » ?${subStepsWarning}`)) {
            return;
        }

        try {
            await apiDelete(this.getStepUrl(step.id));
            this.steps = this.steps.filter((item) => item.id !== step.id);
            this.expandedStepIds.delete(step.id);
            this.render();
            showNotice('success', 'Tâche supprimée.');
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    async deleteSubStep(step, subStep) {
        if (!window.confirm(`Supprimer la sous-tâche « ${subStep.title} » ?`)) {
            return;
        }

        try {
            const updatedStep = await apiDelete(this.getSubStepUrl(step.id, subStep.id));
            this.replaceStep(updatedStep);
            this.render();
            showNotice('success', 'Sous-tâche supprimée.');
        } catch (error) {
            showNotice('error', `Suppression impossible : ${error.message}`);
        }
    }

    /* ------------------------------------------------------------------
       Glisser-déposer (via la poignée uniquement)
       - une tâche se déplace avec ses sous-tâches, parmi les autres tâches
       - une sous-tâche se déplace parmi les sous-tâches de la même tâche
       ------------------------------------------------------------------ */

    handlePointerDown(event) {
        const handle = event.target.closest('[data-handle]');

        if (!handle || !this.isReorderable()) {
            return;
        }

        // La ligne ne devient déplaçable que si on l'attrape par la poignée
        handle.closest('tr').draggable = true;
    }

    handlePointerUp() {
        // Simple clic sur la poignée, sans glisser : la ligne redevient fixe
        if (this.draggedRow) {
            return;
        }

        for (const row of this.list.querySelectorAll('tr[draggable="true"]')) {
            row.draggable = false;
        }
    }

    handleDragStart(event) {
        const row = event.target.closest('tr');

        if (!row || !row.draggable) {
            return;
        }

        this.draggedRow = row;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.id);
        this.placeholder.firstElementChild.style.height = `${row.offsetHeight}px`;

        // On attend que le navigateur ait capturé l'image de la ligne avant de la masquer
        window.requestAnimationFrame(this.hideDraggedRows);
    }

    hideDraggedRows() {
        if (!this.draggedRow) {
            return;
        }

        this.draggedRow.after(this.placeholder);

        for (const row of this.getDraggedGroupRows()) {
            row.classList.add(DRAGGING_CLASS);
        }
    }

    handleDragOver(event) {
        if (!this.draggedRow) {
            return;
        }

        // Indique au navigateur que le dépôt est autorisé ici
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const target = event.target.closest('tr');

        if (!target || target === this.placeholder || target.classList.contains(DRAGGING_CLASS)) {
            return;
        }

        if (this.draggedRow.matches('[data-sub-step]')) {
            this.placeSubStepPlaceholder(target, event.clientY);
        } else {
            this.placeStepPlaceholder(target, event.clientY);
        }
    }

    /**
     * Une tâche se dépose avant une autre tâche, ou après le groupe (tâche + sous-tâches).
     */
    placeStepPlaceholder(target, pointerY) {
        const groupStepId = target.matches('[data-sub-step]') ? target.dataset.parentId : target.dataset.id;
        const groupFirstRow = this.findStepRow(Number(groupStepId));
        const groupRows = [groupFirstRow, ...this.list.querySelectorAll(`[data-sub-step][data-parent-id="${groupStepId}"]`)];
        const groupLastRow = groupRows[groupRows.length - 1];

        if (isInUpperHalf(groupFirstRow, pointerY) && target === groupFirstRow) {
            groupFirstRow.before(this.placeholder);
        } else {
            groupLastRow.after(this.placeholder);
        }
    }

    /**
     * Une sous-tâche ne se dépose qu'entre les sous-tâches de la même tâche.
     */
    placeSubStepPlaceholder(target, pointerY) {
        if (!target.matches('[data-sub-step]') || target.dataset.parentId !== this.draggedRow.dataset.parentId) {
            return;
        }

        if (isInUpperHalf(target, pointerY)) {
            target.before(this.placeholder);
        } else {
            target.after(this.placeholder);
        }
    }

    handleDrop(event) {
        if (!this.draggedRow) {
            return;
        }

        event.preventDefault();

        const row = this.draggedRow;
        const isSubStep = row.matches('[data-sub-step]');
        const ids = isSubStep ? this.readSubStepOrder(row) : this.readStepOrder(row);

        this.finishDrag();

        if (isSubStep) {
            this.saveSubStepOrder(Number(row.dataset.parentId), ids);
        } else {
            this.saveStepOrder(ids);
        }
    }

    handleDragEnd() {
        // Déposée hors du tableau : la ligne reprend sa place
        this.finishDrag();
    }

    finishDrag() {
        if (!this.draggedRow) {
            return;
        }

        for (const row of this.getDraggedGroupRows()) {
            row.classList.remove(DRAGGING_CLASS);
        }

        this.draggedRow.draggable = false;
        this.placeholder.remove();
        this.draggedRow = null;
    }

    /**
     * Ordre des tâches lu dans le tableau, la tâche déplacée étant mise à la place de l'emplacement.
     */
    readStepOrder(draggedRow) {
        const ids = [];

        for (const row of this.list.children) {
            if (row === this.placeholder) {
                ids.push(getRowId(draggedRow));
            } else if (row.matches('[data-step]') && row !== draggedRow) {
                ids.push(getRowId(row));
            }
        }

        return ids;
    }

    readSubStepOrder(draggedRow) {
        const ids = [];
        const parentId = draggedRow.dataset.parentId;

        for (const row of this.list.children) {
            if (row === this.placeholder) {
                ids.push(getRowId(draggedRow));
            } else if (row.matches(`[data-sub-step][data-parent-id="${parentId}"]`) && row !== draggedRow) {
                ids.push(getRowId(row));
            }
        }

        return ids;
    }

    async saveStepOrder(ids) {
        const previousSteps = this.steps;

        this.steps = ids.map((id) => previousSteps.find((step) => step.id === id));
        this.render();

        try {
            await apiPut(this.getOrderUrl(), { ids });
            showNotice('success', 'Ordre enregistré.');
        } catch (error) {
            this.steps = previousSteps;
            this.render();
            showNotice('error', `Ordre non enregistré : ${error.message}`);
        }
    }

    async saveSubStepOrder(stepId, ids) {
        const step = this.findStep(stepId);
        const reorderedStep = {
            ...step,
            subSteps: ids.map((id) => step.subSteps.find((subStep) => subStep.id === id)),
        };

        this.replaceStep(reorderedStep);
        this.renderList();

        try {
            const updatedStep = await apiPut(this.getSubStepOrderUrl(stepId), { ids });
            this.replaceStep(updatedStep);
            this.renderList();
            showNotice('success', 'Ordre enregistré.');
        } catch (error) {
            this.replaceStep(step);
            this.renderList();
            showNotice('error', `Ordre non enregistré : ${error.message}`);
        }
    }

    /**
     * Lignes masquées pendant le déplacement : la tâche et ses sous-tâches, ou la sous-tâche seule.
     */
    getDraggedGroupRows() {
        if (!this.draggedRow) {
            return [];
        }

        if (this.draggedRow.matches('[data-sub-step]')) {
            return [this.draggedRow];
        }

        return [this.draggedRow, ...this.list.querySelectorAll(`[data-sub-step][data-parent-id="${this.draggedRow.dataset.id}"]`)];
    }

    createPlaceholder() {
        const placeholder = document.createElement('tr');
        const cell = document.createElement('td');

        placeholder.className = 'steps-table__placeholder';
        placeholder.setAttribute('aria-hidden', 'true');
        cell.colSpan = COLUMN_COUNT;
        placeholder.append(cell);

        return placeholder;
    }

    /* ------------------------------------------------------------------
       Outils
       ------------------------------------------------------------------ */

    /**
     * À partir d'un élément d'une ligne : la ligne, la tâche, la sous-tâche éventuelle,
     * et "item" = ce qui est modifié (la sous-tâche si c'en est une, sinon la tâche).
     */
    resolveRow(element) {
        const row = element.closest('tr');

        if (row.matches('[data-sub-step]')) {
            const step = this.findStep(Number(row.dataset.parentId));
            const subStep = step?.subSteps.find((item) => item.id === getRowId(row)) ?? null;

            return { row, step, subStep, item: subStep };
        }

        const step = this.findStep(getRowId(row));

        return { row, step, subStep: null, item: step };
    }

    getVisibleSteps() {
        const steps = this.steps.filter((step) => (this.filter === FILTER_ALL || step.status === this.filter)
            && (this.stepMatchesSearch(step) || step.subSteps.some(this.subStepMatchesSearch, this)));

        return this.sortItems(steps);
    }

    /**
     * Sous-tâches affichées sous une tâche :
     * - sans recherche : toutes si la tâche est dépliée ;
     * - avec recherche : celles qui correspondent (toutes si la tâche elle-même correspond et est dépliée).
     */
    getVisibleSubSteps(step) {
        const isExpanded = this.expandedStepIds.has(step.id);

        if (this.searchQuery === '') {
            return isExpanded ? step.subSteps : [];
        }

        if (isExpanded && this.stepMatchesSearch(step)) {
            return step.subSteps;
        }

        return step.subSteps.filter(this.subStepMatchesSearch, this);
    }

    stepMatchesSearch(step) {
        return matchesSearch(`${step.title} ${step.description ?? ''}`, this.searchQuery);
    }

    subStepMatchesSearch(subStep) {
        return this.searchQuery !== '' && matchesSearch(`${subStep.title} ${subStep.description ?? ''}`, this.searchQuery);
    }

    /**
     * Le glisser-déposer n'est possible que sur la liste complète, dans l'ordre manuel, sans recherche.
     */
    isReorderable() {
        return this.filter === FILTER_ALL && this.sortKey === SORT_MANUAL && this.searchQuery === '';
    }

    /* ------------------------------------------------------------------
       Tri par colonne (affichage seulement, l'ordre enregistré ne change pas)
       - les sous-tâches restent sous leur tâche, triées entre elles
       - à valeur égale, l'ordre manuel est conservé
       ------------------------------------------------------------------ */

    /**
     * Copie triée d'une liste de tâches ou de sous-tâches (la liste d'origine reste dans l'ordre manuel).
     */
    sortItems(items) {
        if (this.sortKey === SORT_MANUAL) {
            return items;
        }

        const direction = this.sortDirection === SORT_ASCENDING ? 1 : -1;
        const sorted = items.map((item, index) => ({ item, index, value: this.getSortValue(item) }));

        sorted.sort((a, b) => (compareSortValues(a.value, b.value) * direction) || (a.index - b.index));

        return sorted.map((entry) => entry.item);
    }

    getSortValue(item) {
        switch (this.sortKey) {
            case 'title':
                return item.title;
            case 'description':
                // Sans description : toujours en fin de liste en tri croissant
                return item.description ?? '';
            case 'priority':
                return PRIORITY_RANK[item.priority] ?? 0;
            case 'status':
                return STATUS_RANK[item.status] ?? 0;
            case 'time':
                // Tâche : temps total (somme des sous-tâches) ; sous-tâche : son temps
                return item.totalTimeSpent ?? item.timeSpent;
            default:
                return 0;
        }
    }

    /**
     * Après une modification : la tâche ou une de ses sous-tâches a-t-elle changé de valeur
     * dans la colonne triée ? (si oui, il faut retrier le tableau)
     */
    hasSortPositionChanged(previousStep, updatedStep) {
        if (this.sortKey === SORT_MANUAL) {
            return false;
        }

        if (compareSortValues(this.getSortValue(previousStep), this.getSortValue(updatedStep)) !== 0) {
            return true;
        }

        return previousStep.subSteps.some((subStep) => {
            const updatedSubStep = updatedStep.subSteps.find((item) => item.id === subStep.id);

            return updatedSubStep && compareSortValues(this.getSortValue(subStep), this.getSortValue(updatedSubStep)) !== 0;
        });
    }

    getStepNumber(step) {
        return this.steps.indexOf(step) + 1;
    }

    countByStatus(status) {
        return this.steps.filter((step) => step.status === status).length;
    }

    replaceStep(updatedStep) {
        this.steps = this.steps.map((step) => (step.id === updatedStep.id ? updatedStep : step));
    }

    findStep(id) {
        return this.steps.find((step) => step.id === id) ?? null;
    }

    findStepRow(id) {
        return this.list.querySelector(`[data-step][data-id="${id}"]`);
    }

    findSubStepRow(id) {
        return this.list.querySelector(`[data-sub-step][data-id="${id}"]`);
    }

    getEditField(row, name) {
        return row.querySelector(`[data-edit="${name}"]`);
    }

    getStepUrl(id) {
        return `${this.apiUrl}/${id}`;
    }

    getOrderUrl() {
        return `${this.apiUrl}/ordre`;
    }

    getSubStepCreateUrl(stepId) {
        return `${this.apiUrl}/${stepId}/sous-taches`;
    }

    getSubStepOrderUrl(stepId) {
        return `${this.apiUrl}/${stepId}/sous-taches/ordre`;
    }

    getSubStepUrl(stepId, id) {
        return `${this.apiUrl}/${stepId}/sous-taches/${id}`;
    }

    setStat(name, text) {
        this.root.querySelector(`[data-stat="${name}"]`).textContent = text;
    }
}

/**
 * Compare deux valeurs de tri : nombres, ou textes en français (accents et majuscules ignorés).
 * Un texte vide passe après les autres.
 */
function compareSortValues(a, b) {
    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    if (a === '' || b === '') {
        return (a === '') - (b === '');
    }

    return a.localeCompare(b, 'fr', { sensitivity: 'base', numeric: true });
}

function addTotalTimeSpent(total, step) {
    return total + step.totalTimeSpent;
}

function isDone(item) {
    return item.status === STATUS_DONE;
}

function isInUpperHalf(row, pointerY) {
    const box = row.getBoundingClientRect();

    return pointerY < box.top + (box.height / 2);
}

function getRowId(row) {
    return Number(row.dataset.id);
}

/**
 * Ne remplace pas la valeur d'un champ en cours de saisie
 * (sinon la réponse du serveur effacerait ce que l'utilisateur tape).
 */
function setFieldValue(field, value) {
    if (field !== document.activeElement) {
        field.value = value;
    }
}

function readPositiveInteger(field) {
    return Math.max(0, Number.parseInt(field.value, 10) || 0);
}

function removeSavedClass(event) {
    event.currentTarget.classList.remove(SAVED_CLASS);
}
