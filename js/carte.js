document.addEventListener("DOMContentLoaded", function () {
    
    // Function to get highlight IDs from URL parameters
    function getHighlightIds() {
        const urlParams = new URLSearchParams(window.location.search);
        const highlight = urlParams.get('abr');
        if (!highlight) return [];
        return highlight.split(',').map(id => parseInt(id.trim()));
    }
    
    /**
     * Initialise une carte Leaflet avec des marqueurs représentant des arbres.
     *
     * @param {string} mapId - L'ID de l'élément HTML où la carte sera rendue.
     * @param {Object} config - Objet de configuration pour la carte.
     * @param {number} [config.zoom=19] - Le niveau de zoom initial de la carte.
     * @param {string} config.jsonUrl - L'URL pour récupérer les données des arbres au format JSON.
     *
     * Les données des arbres doivent être un tableau d'objets avec la structure suivante :
     * {
     *   numero: {number} - L'identifiant unique de l'arbre.
     *   lat: {number} - La latitude de l'emplacement de l'arbre.
     *   lng: {number} - La longitude de l'emplacement de l'arbre.
     *   nom: {string} - Le nom de l'arbre.
     *   url: {string} - Une URL avec plus d'informations sur l'arbre.
     * }
     *
     * Met en surbrillance certains marqueurs d'arbres en fonction de leurs IDs, récupérés via `getHighlightIds()`.
     * Chaque marqueur affiche une popup avec le nom de l'arbre et un lien pour plus d'informations.
     *
     * @throws Affiche une erreur dans la console si le chargement des données des arbres échoue.
     */
    function initMap(mapId, config) {
        const highlightIds = getHighlightIds();
        
        // Créer la carte avec les paramètres de configuration
        const map = L.map(mapId).setView([43.630204634227, -1.0328006744385], config.zoom || 19);
        
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        
        // Fetch arbre data and create markers
        fetch(config.jsonUrl)
            .then(response => response.json())
            .then(data => {
                data.forEach(arbre => {
                    const isHighlighted = highlightIds.includes(Number(arbre.numero));
                    const marker = L.marker([arbre.lat, arbre.lng], {
                        icon: L.divIcon({
                            className: 'custom-div-icon',
                            html: `<div class="pastille ${isHighlighted ? 'highlight' : ''}">${arbre.numero}</div>`,
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        })
                    }).addTo(map);
                    
                    marker.bindPopup(`
                        <p class="popup-title">${arbre.nom}</p>
                        <a href="${arbre.url}">Plus d'infos</a>
                    `);
                });
            })
            .catch(error => {
                console.error('Erreur lors du chargement des données des arbres:', error);
            });
    }
    
    // Initialiser toutes les cartes présentes sur la page
    document.querySelectorAll('.carte-arboretum').forEach(function(mapElement) {
        const configName = mapElement.getAttribute('data-config');
        const config = window[configName];
        
        if (config) {
            initMap(mapElement.id, config);
        } else {
            // Fallback pour la compatibilité avec l'ancien système
            if (mapElement.id === 'map') {
                initMap('map', {
                    jsonUrl: '/wp-content/uploads/carte-data/arbres.json',
                    zoom: 19
                });
            }
        }
    });
    
    // Compatibilité avec l'ancien shortcode (si l'élément #map existe encore)
    const oldMapElement = document.getElementById('map');
    if (oldMapElement && !oldMapElement.classList.contains('carte-arboretum')) {
        initMap('map', {
            jsonUrl: '/wp-content/uploads/carte-data/arbres.json',
            zoom: 19
        });
    }
    
});