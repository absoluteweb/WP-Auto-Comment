# WP Auto Comment

Extension WordPress qui automatise la génération de commentaires sur les articles de blog en utilisant l'intelligence artificielle OpenAI.

## 🆕 Nouveautés - Contextualisation des Personas

**Version améliorée** : Les personas sont maintenant automatiquement adaptés à la thématique de votre site !

### Fonctionnalités de contextualisation :

- **Analyse automatique** de votre site (catégories, tags, contenu)
- **Détection de secteur** parmi 8 thématiques : cuisine, technologie, lifestyle, santé, business, éducation, famille, loisirs
- **Adaptation des personas** : professions et styles d'écriture cohérents avec votre niche
- **Interface visuelle** : aperçu de l'analyse dans l'admin WordPress
- **Contrôle total** : activation/désactivation de la contextualisation

### Exemple d'amélioration :

**Avant** : Persona générique → "Jean, 30 ans, comptable"
**Après** : Site de cuisine → "Marie, 28 ans, chef pâtissière passionnée de nouvelles saveurs"

## Fonctionnalités principales

- 🤖 **Génération automatique** de commentaires via OpenAI GPT
- 👥 **Système de personas** varié et contextuel  
- ⏰ **Planification flexible** : par durée ou par visites
- 🎯 **Ciblage intelligent** : commentaires adaptés au contenu
- 📊 **Interface complète** : gestion depuis l'admin WordPress
- 🔄 **Mise à jour automatique** via GitHub

## Installation

1. Téléchargez et activez le plugin
2. Configurez votre clé API OpenAI dans Réglages → WP Auto Comment
3. Activez la contextualisation automatique (recommandé)
4. Générez des modèles de personas adaptés à votre thématique
5. Activez les commentaires automatiques sur vos articles

## Configuration

### Réglages généraux
- Clé API OpenAI (obligatoire)
- Modèle GPT (gpt-4o-mini recommandé)
- Nombre de mots par commentaire (5-20)

### Contextualisation des personas
- **Analyse automatique** : Le plugin détecte votre secteur d'activité
- **Adaptation intelligente** : Les personas générés correspondent à votre audience
- **Contrôle manuel** : Possibilité de désactiver la contextualisation

### Modes de publication
- **Par durée** : X commentaires toutes les Y minutes
- **Par visites** : X commentaires toutes les Y adresses IP uniques

## Utilisation

1. **Génération manuelle** : Sélectionnez des articles et utilisez l'action en lot
2. **Génération automatique** : Cochez "Commentaire automatique" sur vos articles
3. **Personas contextuels** : Générés automatiquement selon votre thématique

## Sécurité

- Vérification des nonces AJAX
- Validation des données d'entrée  
- Contrôle d'accès administrateur
- Limitation du nombre de commentaires

## Support

Pour toute question ou suggestion d'amélioration, contactez l'équipe de développement.

---

*Plugin développé par Kevin BENABDELHAK - Version 2.2+*
