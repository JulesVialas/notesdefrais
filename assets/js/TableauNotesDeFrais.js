/**
 * @fileoverview Gestion de l'affichage tabulaire des notes de frais
 */

let activeTab = 'enCours';
// Use the window.appBaseUrl variable set in the HTML
let baseUrl = window.appBaseUrl || '';

/**
 * Change l'onglet actif et actualise l'affichage du tableau des notes de frais
 *
 * @param {string} tabName - Nom de l'onglet à afficher ('enCours', 'terminees', ou 'refusees')
 * @return {void}
 */
function showTab(tabName) {
    console.log('Switching to tab:', tabName);
    activeTab = tabName;
    document.querySelectorAll('.btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
    const tableBody = document.getElementById('notesDeFraisTable');
    tableBody.innerHTML = '';
    const data = notesDeFrais[tabName];
    if (data.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="6" class="text-center p-4">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucune note de frais trouvée
                </div>
            </td>
        `;
        tableBody.appendChild(row);
        return;
    }
    data.forEach(note => {
        const row = document.createElement('tr');
        row.setAttribute('data-id', note.Identifiant);
        const formattedDate = note.DateDemande ? new Date(note.DateDemande).toLocaleDateString('fr-FR') : 'N/A';
        let statusBadgeClass = 'bg-secondary';
        if (note.Statut && note.Statut.toLowerCase().includes('cours')) {
            statusBadgeClass = 'bg-primary';
        } else if (note.Statut === 'Terminée') {
            statusBadgeClass = 'bg-success';
        } else if (note.Statut === 'Refusée') {
            statusBadgeClass = 'bg-danger';
        }
        let actionButtons = `
            <a href="${baseUrl}voir-note-frais/${note.Identifiant}" class="btn btn-sm btn-outline-primary me-1">
                <i class="fas fa-eye me-1"></i>Détails
            </a>`;

        if (note.Statut === 'En cours de saisie' || note.Statut === 'Refusée') {
            actionButtons += `
                <a href="${baseUrl}modifier-note-frais/${note.Identifiant}" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit me-1"></i>Modifier
                </a>`;
        }
        row.innerHTML = `
            <td>${note.Identifiant}</td>
            <td>${formattedDate}</td>
            <td><span class="badge ${statusBadgeClass}">${note.Statut}</span></td>
            <td class="text-end">${formatCurrency(note.TotalTTC)}</td>
            <td class="text-end">${formatCurrency(note.TotalTVA)}</td>
            <td class="text-center">${actionButtons}</td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * Formate une valeur numérique en devise Euro selon le format français
 */
function formatCurrency(value) {
    if (value === undefined || value === null) return 'N/A';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2
    }).format(parseFloat(value));
}

/**
 * Initialise le comportement du tableau au chargement de la page
 */
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    if (tabParam && ['enCours', 'terminees', 'refusees'].includes(tabParam)) {
        showTab(tabParam);
    } else {
        showTab('enCours');
    }

    function updateViewLinks() {
        document.querySelectorAll(`a[href^="${baseUrl}voir-note-frais/"]`).forEach(link => {
            const href = link.getAttribute('href').split('?')[0];
            link.setAttribute('href', `${href}?tab=${activeTab}`);
        });
    }

    updateViewLinks();
    const originalShowTab = showTab;
    window.showTab = function (tabName) {
        originalShowTab(tabName);
        updateViewLinks();
    };
});