/**
 * Remplit automatiquement le champ de motif de rejet d'une note de frais
 *
 * @param {number} noteId - L'identifiant de la note de frais concernée
 * @param {string} motif - Le texte du motif de rejet à injecter
 * @return {void}
 */
function remplirMotif(noteId, motif) {
    if (motif) {
        document.getElementById('motif' + noteId).value = motif;
    }
}