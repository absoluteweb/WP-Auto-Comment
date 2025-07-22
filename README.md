# WP Auto Comment

Extension WordPress qui automatise la génération de commentaires sur les articles de blog en utilisant l'intelligence artificielle OpenAI.

## 🆕 Nouveautés - Détection OpenAI améliorée

**Version 2.3** : Détection de thématique ultra-précise avec OpenAI !

### 🎯 Problème résolu :
- **Avant** : Détection par mots-clés parfois imprécise (site santé classé en "technologie")
- **Maintenant** : Analyse contextuelle OpenAI pour une précision maximale

### 🚀 Nouvelles fonctionnalités :

#### **Détection OpenAI intelligente :**
- **Analyse contextuelle** : OpenAI examine le contenu réel, pas seulement les mots-clés
- **Précision maximale** : Comprend les nuances et le contexte global du site
- **8 secteurs détectés** : cuisine, technologie, lifestyle, santé, business, éducation, famille, loisirs

#### **Système de cache optimisé :**
- **Cache 30 jours** : Une seule analyse OpenAI par mois
- **Économie d'API** : Pas de requête répétée à chaque génération de persona
- **Indicateur visuel** : Affichage de la date de dernière analyse

#### **Contrôle total :**
- **Bouton "🔄 Relancer la détection"** : Re-analyse immédiate si votre site évolue
- **Choix de méthode** : OpenAI (recommandé) ou mots-clés locaux (gratuit)
- **Fallback automatique** : Si problème API, bascule vers la méthode locale

### 🔧 Interface améliorée :

```
📊 Analyse de votre site :
Secteur détecté : Santé (Analysé par OpenAI le 15/01/2025 à 14:30)
Principales catégories : Nutrition, Fitness, Bien-être
Tags populaires : santé, sport, alimentation, médecine

[🔄 Relancer la détection] ✅ Détection mise à jour avec succès !
```

### 💡 Exemple concret :

**Site santé mal détecté :**
- **Ancien système** : "technologie" (car mots comme "digital", "app santé")
- **Nouveau système OpenAI** : "santé" (comprend le contexte global)

**Résultat :**
- **Personas avant** : "Thomas, développeur web, amateur de gadgets"
- **Personas après** : "Dr. Sarah, nutritionniste, spécialisée en bien-être"

## Fonctionnalités principales

- 🤖 **Génération automatique** de commentaires via OpenAI GPT
- 👥 **Système de personas** varié et contextuel  
- 🎯 **Détection OpenAI précise** de la thématique du site
- ⚡ **Cache intelligent** pour optimiser les coûts API
- ⏰ **Planification flexible** : par durée ou par visites
- 📊 **Interface complète** : gestion depuis l'admin WordPress
- 🔄 **Mise à jour automatique** via GitHub

## Installation

1. Téléchargez et activez le plugin
2. Configurez votre clé API OpenAI dans Réglages → WP Auto Comment
3. **Nouveau** : Choisissez "Analyse OpenAI" pour une détection précise
4. Générez des modèles de personas adaptés à votre thématique
5. Activez les commentaires automatiques sur vos articles

## Configuration

### Réglages généraux
- Clé API OpenAI (obligatoire)
- Modèle GPT (gpt-4o-mini recommandé)
- Nombre de mots par commentaire (5-20)

### Contextualisation des personas
- **Analyse automatique** : Le plugin détecte votre secteur d'activité avec OpenAI
- **Cache optimisé** : Une seule analyse par mois pour économiser l'API
- **Bouton de re-détection** : Relancez l'analyse si votre site évolue
- **Fallback local** : Méthode gratuite par mots-clés si besoin

### Modes de publication
- **Par durée** : X commentaires toutes les Y minutes
- **Par visites** : X commentaires toutes les Y adresses IP uniques

## Utilisation

1. **Génération manuelle** : Sélectionnez des articles et utilisez l'action en lot
2. **Génération automatique** : Cochez "Commentaire automatique" sur vos articles
3. **Personas contextuels** : Générés automatiquement selon votre thématique détectée par OpenAI
4. **Re-détection** : Utilisez le bouton "🔄 Relancer la détection" si votre site change de thématique

## Sécurité

- Vérification des nonces AJAX
- Validation des données d'entrée  
- Contrôle d'accès administrateur
- Limitation du nombre de commentaires

## Support

Pour toute question ou suggestion d'amélioration, contactez l'équipe de développement.

---

*Plugin développé par Kevin BENABDELHAK - Version 2.3+*
