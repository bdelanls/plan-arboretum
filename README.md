# Plan Arboretum

Extension WordPress pour créer et afficher une carte interactive d'un arboretum avec géolocalisation des arbres.

## 📋 Description

Cette extension permet de créer une carte interactive pour présenter les arbres d'un arboretum. Elle gère la conversion de coordonnées géographiques, génère automatiquement un fichier JSON des données et affiche une carte Leaflet avec des marqueurs personnalisés.

## ✨ Fonctionnalités

- 🗺️ **Carte interactive** avec Leaflet (OpenStreetMap)
- 📍 **Marqueurs personnalisés** avec numérotation des arbres
- 🔄 **Conversion de coordonnées** RGF93 CC44 vers WGS84
- 📄 **Génération automatique** de fichier JSON
- 🎯 **Mise en évidence** d'arbres spécifiques via URL
- ⚙️ **Shortcode configurable** avec paramètres personnalisables
- 🔧 **Interface d'administration** dédiée

## 🛠️ Installation

1. Téléchargez l'extension
2. Décompressez le fichier dans `/wp-content/plugins/`
3. Activez l'extension dans l'administration WordPress
4. Assurez-vous d'avoir le custom post type "arbre" configuré

## 📦 Dépendances

- **WordPress** 5.0+
- **PHP** 7.4+
- **Custom Post Type** "arbre" avec les champs :
  - `id_arbre` : Numéro unique de l'arbre
  - `easting` : Coordonnée Est (projection RGF93 CC44)
  - `northing` : Coordonnée Nord (projection RGF93 CC44)

### Bibliothèques incluses

- **Proj4php** : Conversion de projections cartographiques
- **Leaflet** : Bibliothèque de cartographie interactive

## 🚀 Utilisation

### Shortcode de base

```
[plan_arboretum]
```

### Shortcode avec paramètres

```
[plan_arboretum height="300px" zoom="18"]
[plan_arboretum width="80%" zoom="16"]
[plan_arboretum height="400px" width="90%" zoom="17"]
```

### Paramètres disponibles

| Paramètre | Description | Défaut | Exemple |
|-----------|-------------|--------|---------|
| `height` | Hauteur de la carte | `500px` | `height="300px"` |
| `width` | Largeur de la carte | `100%` | `width="80%"` |
| `zoom` | Niveau de zoom initial | `15` | `zoom="18"` |

### Mise en évidence d'arbres

Ajoutez le paramètre `abr` à l'URL pour mettre en évidence des arbres spécifiques :

```
https://votre-site.com/carte/?abr=123,456,789
```

## ⚙️ Administration

L'extension ajoute une sous-page "Générer JSON" dans le menu du custom post type "Arbre".

### Fonctionnalités d'administration

- **Génération manuelle** du fichier JSON
- **Vérification du statut** de synchronisation
- **Rapport d'erreurs** pour les arbres sans coordonnées
- **Documentation** du shortcode intégrée

### Processus de génération

1. Récupère tous les arbres publiés
2. Valide les métadonnées (numéro, coordonnées)
3. Convertit les coordonnées RGF93 CC44 → WGS84
4. Génère le fichier `/wp-content/uploads/carte-data/arbres.json`

## 📁 Structure du projet

```
plan-arboretum/
├── plan-arboretum.php          # Fichier principal
├── inc/
│   └── functions.php           # Fonctions métier
├── js/
│   └── carte.js               # Script de la carte Leaflet
├── leaflet/                   # Bibliothèque Leaflet
├── proj4php/                  # Bibliothèque Proj4php
└── README.md
```

## 🗂️ Format des données

Le fichier JSON généré suit cette structure :

```json
[
  {
    "id": 123,
    "numero": "001",
    "nom": "Chêne pédonculé",
    "lat": 43.630204,
    "lng": -1.032800,
    "url": "/arbre/chene-pedoncule"
  }
]
```

## 🎨 Personnalisation CSS

L'extension génère des marqueurs avec les classes CSS suivantes :

```css
.custom-div-icon {
  /* Conteneur du marqueur */
}

.pastille {
  /* Style de base du marqueur */
}

.pastille.highlight {
  /* Style du marqueur mis en évidence */
}

.popup-title {
  /* Titre dans la popup */
}
```

## 🔧 Configuration technique

### Conversion de coordonnées

L'extension utilise la projection **RGF93 CC44 (EPSG:3944)** avec les paramètres :

```
+proj=lcc +lat_1=43.199291 +lat_2=44.800709 +lat_0=44 +lon_0=3 +x_0=1700000 +y_0=3200000 +ellps=GRS80 +units=m +no_defs
```

### Carte par défaut

- **Centre** : `[43.630204634227, -1.0328006744385]`
- **Zoom** : 19
- **Tuiles** : OpenStreetMap
- **Zoom maximum** : 19

## 🐛 Dépannage

### Le fichier JSON n'est pas généré

1. Vérifiez les permissions du dossier `/wp-content/uploads/`
2. Assurez-vous que les arbres ont bien les métadonnées requises
3. Consultez les erreurs dans l'interface d'administration

### La carte ne s'affiche pas

1. Vérifiez que le fichier JSON existe et est accessible
2. Ouvrez la console développeur pour voir les erreurs JavaScript
3. Vérifiez que Leaflet est bien chargé

### Coordonnées incorrectes

1. Vérifiez que les coordonnées source sont bien en projection RGF93 CC44
2. Contrôlez les valeurs `easting` et `northing` dans l'administration

## 📝 Changelog

### Version 1.8

- Ajout du shortcode configurable
- Support multi-instances de cartes
- Interface d'administration améliorée
- Vérification du statut de synchronisation
- Documentation intégrée

## 👨‍💻 Auteur

**Bertrand Delanssays**
- Site web : [https://www.bdelanls.fr](https://www.bdelanls.fr)

## 📄 Licence

Cette extension est distribuée sous licence GPL2.

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos modifications
4. Créer une Pull Request

## 📞 Support

Pour toute question ou problème, ouvrez une issue sur ce repository.
