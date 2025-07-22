# WP Auto Comment

Extension WordPress qui automatise la génération de commentaires sur les articles de blog en utilisant l'intelligence artificielle OpenAI.

## 🆕 Nouveautés - Mode IP avec sélection aléatoire

**Version 2.4** : Distribution naturelle des commentaires avec sélection aléatoire !

### 🎯 Problème résolu :
- **Avant** : Commentaires ajoutés systématiquement à tous les articles (peu naturel)
- **Maintenant** : Sélection aléatoire d'articles pour un rendu plus naturel

### 🎲 Nouvelle logique "mode par visites" :

#### **Sélection aléatoire intelligente :**
- **Articles choisis au hasard** : Parmi tous ceux ayant l'auto-comment activé
- **Distribution naturelle** : Évite la systématisation visible
- **Contrôle précis** : X commentaires sur X articles aléatoires toutes les Y visites

#### **Exemple concret :**
```
Configuration : "2 commentaires / 15 IP"
• 100 articles ont l'auto-comment activé
• Toutes les 15 visites uniques → sélection de 2 articles au hasard
• Chaque article sélectionné reçoit 1 commentaire
• Les 98 autres articles restent intacts (cette fois)
```

#### **Interface de contrôle :**
```
📊 Statut actuel :
IP uniques collectées : 12 / 15
Prochains commentaires dans : 3 IP
Dernières IP : 192.168.1.5, 10.0.0.1, 172.16.0.3...

[🔄 Réinitialiser le compteur IP] ✅ Compteur réinitialisé !
```

#### **Indicateurs visuels :**
- **🎲 Sélection aléatoire** : Affiché sur les articles en mode IP
- **Compteur en temps réel** : Suivi des IP collectées
- **Bouton de reset** : Réinitialisation manuelle si besoin

### 💡 Comparaison avant/après :

**Ancien système :**
- 15 IP → Commentaires sur TOUS les articles éligibles
- Résultat : 50 commentaires simultanés (suspect)

**Nouveau système :**
- 15 IP → 2 articles aléatoires sélectionnés
- Résultat : 2 commentaires répartis naturellement

## 🆕 Détection OpenAI améliorée

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
- 🎲 **Sélection aléatoire** pour commentaires naturels (mode IP)
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
- **Par durée** : X commentaires toutes les Y minutes (distribution séquentielle)
- **Par visites** : X commentaires aléatoires toutes les Y adresses IP uniques

## Utilisation

1. **Génération manuelle** : Sélectionnez des articles et utilisez l'action en lot
2. **Génération automatique** : Cochez "Commentaire automatique" sur vos articles
3. **Personas contextuels** : Générés automatiquement selon votre thématique détectée par OpenAI
4. **Re-détection** : Utilisez le bouton "🔄 Relancer la détection" si votre site change de thématique
5. **Mode IP naturel** : Les articles sont sélectionnés aléatoirement pour plus de naturel

## Sécurité

- Vérification des nonces AJAX
- Validation des données d'entrée  
- Contrôle d'accès administrateur
- Limitation du nombre de commentaires

## Support

Pour toute question ou suggestion d'amélioration, contactez l'équipe de développement.

---

*Plugin développé par Kevin BENABDELHAK - Version 2.4+*
