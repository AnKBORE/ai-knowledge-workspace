# Sécurité et confidentialité des données — API d'inférence

🇬🇧 English version: [SECURITE_DONNEES_API.md](SECURITE_DONNEES_API.md)

> **Objet** : préciser ce que le projet envoie (ou n'envoie pas) à l'API d'inférence (Baseten / Muse Spark Open), qui peut voir ces données, quels sont les risques réels et quelles protections sont en place.
>
> **Public** : jury, auditeurs, responsables sécurité et toute partie prenante soucieuse de la confidentialité.

---

## 1. Principe

Le projet utilise un **modèle open-weight de Meta** (Muse Spark Open) **servi par un partenaire d'inférence** (Baseten). Ce n'est **pas** un « vol » de données : c'est un **traitement** de données par un **sous-traitant**, encadré par un contrat (conditions d'utilisation et **DPA**).

> ⚠️ **Nuance importante** : la confidentialité n'est pas garantie à 100 % — les données **sortent** de l'infrastructure de l'organisation pour être **traitées** chez le partenaire. C'est un **choix d'architecture** à assumer, et à **encadrer**.

---

## 2. Données qui transitent vers l'API

| Donnée | Envoyée ? | Quand |
|---|---|---|
| **La question de l'utilisateur** | ✅ Oui | À chaque question |
| **Les extraits de documents** (top-K) | ✅ Oui | À chaque question / tâche / résumé |
| **Les images** (scans) | ✅ Oui (base64) | À l'upload d'une image (OCR) |
| **Les audios** | ✅ Oui (base64) | À l'upload d'un audio (transcription) |
| **Le contexte système** (prompt) | ✅ Oui | À chaque appel |

> ✅ **Seuls les extraits pertinents** (top-K) sont envoyés — **jamais le document entier**.

---

## 3. Données qui **ne** transitent **jamais**

| Donnée | Statut |
|---|---|
| **Mots de passe** (hachés Argon2) | ❌ Jamais |
| **Jetons CSRF / sessions** | ❌ Jamais |
| **Empreintes SHA-256** (documents) | ❌ Jamais (calculées localement) |
| **Embeddings** (vecteurs d'indexation) | ❌ Jamais — **calculés localement** (hashing trick) |
| **Journal d'audit** | ❌ Jamais (stocké en base) |
| **Fichiers bruts** | ❌ Jamais (stockés hors racine web) |

---

## 4. Qui peut voir quoi

| Acteur | Voit-il les données ? | Détail |
|---|---|---|
| **Baseten** (hébergeur d'inférence) | ✅ **Oui** | Exécute le modèle ; peut journaliser pour l'anti-abus |
| **Meta** | ❌ **Non** | Le modèle est **open-weight**, exécuté **chez Baseten** |
| **Ton serveur** | ✅ Oui | Source des données |
| **Tiers** | ❌ Non | Pas d'accès |

> **Point clé** : Meta **ne reçoit pas** les données du projet — l'inférence est réalisée par Baseten.

---

## 5. « Vol » ou « traitement » ?

| Terme | Réalité |
|---|---|
| **Vol** | ❌ Non — Baseten ne « vole » rien ; c'est un **service commercial** |
| **Traitement** | ✅ Oui — tes données sont **traitées** sur ses serveurs |
| **Journalisation** | ⚠️ Possible (anti-abus) — à vérifier dans la politique |
| **Entraînement sur tes données** | ⚠️ À vérifier dans les conditions du partenaire |

---

## 6. Risques réels

| Risque | Niveau | Détail |
|---|---|---|
| **Confidentialité** (données sensibles) | 🟠 Réel | Le partenaire voit le texte envoyé |
| **Résidence des données** | 🟠 À vérifier | Serveurs hors Burkina Faso (UE/États-Unis) |
| **Rétention / logs** | 🟡 À vérifier | Durée de conservation chez le partenaire |
| **Interception réseau** | ✅ Protégé | TLS 1.2+ (bundle CA à jour) |
| **Fuite par un tiers** | 🟡 Faible | Dépend du partenaire |

---

## 7. Mesures de protection

| Mesure | Effet | Statut |
|---|---|---|
| **Anonymisation** avant envoi | Retirer noms, adresses, numéros | 🔧 À faire selon les données |
| **Envoi du top-K uniquement** | Minimisation des données | ✅ Déjà fait |
| **Embeddings locaux** | Aucun envoi à l'indexation | ✅ Déjà fait |
| **Stockage hors racine web** | Fichiers non exposés | ✅ Déjà fait |
| **SHA-256** | Intégrité + non-répudiation | ✅ Déjà fait |
| **Journal d'audit** | Traçabilité des accès | ✅ Déjà fait |
| **Self-host** de Muse Spark Open | Zéro sortie de données | 🔧 Optionnel (poids ouverts) |
| **DPA** avec le partenaire | Cadre contractuel | 🔧 À vérifier |

---

## 8. Conformité

| Point | Recommandation |
|---|---|
| **RGPD** (si données personnelles) | Vérifier le **DPA**, la **base légale**, les **transferts hors UE** |
| **Données sensibles** (santé, juridique) | **Anonymiser** ou **self-host** |
| **Consentement** | Informer les utilisateurs du traitement par IA |
| **Registre des traitements** | Tenir un registre (obligation RGPD) |

---

## 9. Ce qui est déjà fait dans le projet

| Élément | Statut |
|---|---|
| Embeddings **locaux** (pas d'API) | ✅ |
| Envoi du **top-K** uniquement | ✅ |
| Stockage **hors racine web** | ✅ |
| Empreintes **SHA-256** | ✅ |
| **Journal d'audit** détaillé | ✅ |
| En-têtes de sécurité (**CSP**) | ✅ |
| **Bundle CA** à jour (TLS) | ✅ |
| Mots de passe **Argon2** | ✅ |

---

## 10. Recommandations

1. **Anonymiser** les documents sensibles avant indexation.
2. **Vérifier le DPA** et la politique de rétention du partenaire.
3. **Envisager le self-host** de Muse Spark Open (poids ouverts) pour les données critiques.
4. **Documenter** le traitement (registre RGPD).
5. **Informer** les utilisateurs (mention « traitement par IA »).

---

## 11. Conclusion

| Question | Réponse |
|---|---|
| Y a-t-il **vol** de données ? | ❌ **Non** (service contractuel) |
| Les données **sortent-elles** ? | ✅ **Oui** (vers Baseten) |
| Meta les reçoit-il ? | ❌ **Non** (modèle open-weight chez Baseten) |
| Est-ce **risqué** ? | 🟡 **Dépend de la sensibilité** |
| Peut-on **éviter** ? | ✅ **Oui** (anonymisation, self-host, top-K) |

> **Pour le hackathon** (données de démonstration), le risque est **négligeable**. Pour un **déploiement réel** avec des données sensibles, appliquer les mesures de la section 7.

---

## 12. Références

- Politique de confidentialité du partenaire d'inférence (Baseten).
- Conditions du **Meta Global AI Developer Hackathon** (§3.8 — modèles open-weight via partenaires).
- RGPD — règlement (UE) 2016/679 (si données personnelles).
- [`docs/GUIDE_DE_REALISATION.md`](GUIDE_DE_REALISATION.fr.md) — architecture et sécurité du projet.

---

## 13. Checklist DPA (à vérifier auprès du partenaire)

Avant tout déploiement avec des données sensibles, obtenir et vérifier :

| Point | Question à poser | Réponse attendue |
|---|---|---|
| **DPA** | Un Data Processing Agreement est-il disponible ? | ✅ Signé |
| **Rétention** | Combien de temps les requêtes sont-elles conservées ? | Durée minimale (ex. 30 jours) |
| **Sous-traitants** | Y a-t-il des sous-traitants ultérieurs ? | Liste fournie |
| **Localisation** | Où sont hébergés les serveurs ? | UE / US (à documenter) |
| **Entraînement** | Les données servent-elles à entraîner des modèles ? | Non (par défaut) |
| **Suppression** | Peut-on demander la suppression des données ? | Oui, sur demande |
| **Notification de violation** | En cas de fuite, sous quel délai sommes-nous informés ? | 72 h (RGPD) |

> 📌 **Action** : conserver le DPA signé et le joindre au registre des traitements.

---

## 14. Self-host de Muse Spark Open (zéro sortie)

Muse Spark Open est un modèle **open-weight** : il peut être exécuté **sur votre propre infrastructure**, ce qui supprime toute sortie de données.

### Option A — Serveur GPU dédié (recommandé pour la production)

1. Provisionner un serveur GPU (ex. 1× A10G / L4).
2. Servir le modèle avec **vLLM** ou **TGI** (endpoint compatible OpenAI).
3. Dans `.env` :

```dotenv
META_API_STYLE=openai
META_MODEL_API_BASE_URL=http://localhost:8000/v1
META_MODEL_NAME=meta-models/muse-spark-open
META_API_KEY=local
```

4. Vérifier : `curl http://localhost:8000/v1/models`.

### Option B — Poste de travail (développement)

- Modèle quantifié (4 bits) sur GPU 16 Go.
- **Données de démonstration uniquement** (performances réduites).

### Ce que ça change

| Élément | Avant (Baseten) | Après (self-host) |
|---|---|---|
| **Sortie de données** | ✅ Oui | ❌ **Non** |
| **Coût** | Par token | **Coût fixe** (GPU) |
| **Performance** | Optimale | Selon le matériel |
| **Conformité** | Dépend du partenaire | **Totale** |

> ⚠️ Le self-host nécessite des **compétences GPU** et un **budget matériel** — à réserver aux déploiements avec données critiques.

---

## 15. Configuration d'anonymisation du projet

Le projet intègre un **anonymiseur** (e-mails, téléphones, IBAN, cartes bancaires) appliqué **avant l'indexation** et **avant tout appel API**.

```dotenv
# .env
ANONYMISATION=1
```

| Action | Effet |
|---|---|
| `ANONYMISATION=0` (défaut) | Texte indexé tel quel |
| `ANONYMISATION=1` | Les identifiants directs sont remplacés par `[EMAIL]`, `[TELEPHONE]`, `[IBAN]`, `[CARTE]` |

> ✅ Implémenté dans `services/Anonymiseur.php`, testé par `tests/AnonymiseurTest.php`.
---

## 16. Purge légale du journal d'audit

Le journal est **en ajout seul par conception**. Il existe **une** exception encadrée : la **purge légale** (droit à l'effacement, RGPD), réservée à l'**administrateur technique**.

| Contrôle | Détail |
|---|---|
| **Permission** | `journal.lire` (administrateur technique uniquement) |
| **Méthode** | **POST** + **CSRF** obligatoire |
| **Re-authentification** | **Mot de passe du compte** exigé à chaque purge |
| **Fenêtre** | Seules les entrées de **plus de 10 jours** peuvent être purgées |
| **Traçabilité** | La purge est **elle-même journalisée** (`journal.purge`, avec le nombre d'entrées) |
| **Refus** | Un mot de passe incorrect est **journalisé** (`journal.purge`, résultat `refus`) |

> ✅ **Rien n'échappe au journal** : même la purge **laisse une trace**. Les entrées purgées sont **anciennes** (> 10 jours) et la purge est **restreinte, re-authentifiée et auditée**.

---

## 17. Endpoints de correction et de reformulation

Ces endpoints manipulent des contenus rédigés par un humain : ils sont donc
traités avec les mêmes contrôles que les documents.

| Endpoint | Méthode | Contrôles |
|---|---|---|
| `correction_save.php` | POST multipart | Auth · **CSRF** · `document.deposer` · rate limit 30/5 min · 24 Mo · extensions `.pdf/.docx/.txt/.md/.csv` · doc non supprimé |
| `correction_list.php` | GET | Auth · `document.consulter` · `document_id` obligatoire |
| `correction_delete.php` | POST | Auth · **CSRF** · auteur de la correction **ou** `document.supprimer` |
| `correction_download.php` | GET | Auth · `document.consulter` · `basename()` + nettoyage du nom · `no-store` · journalisé |
| `reformulate.php` | POST | Auth · **CSRF** · rate limit 30/5 min · accepte un audio (24 Mo), un texte ou un `document_id` |

### Ce qui est journalisé

| Action | Détail enregistré |
|---|---|
| `document.correction` | langue, nombre de caractères |
| `document.correction.suppression` | identifiant, langue |
| `document.correction.telechargement` | identifiant, langue, source (fichier ou texte) |
| `reformulation` | source (audio / texte), langues, longueur |

### Ce qui ne quitte jamais le serveur

Le **fichier corrigé** reste dans `storage/files/corrections/`. Seul son
**texte** est envoyé à l'API, comme n'importe quel document indexé.

### Lecture des corrections

Les corrections déposées par un locuteur natif sont **validées par nature** : il
n'existe pas de workflow d'approbation. Le contrôle est **a posteriori** :
n'importe quel ayant-droit peut lister et supprimer une correction erronée, et
la suppression cesse immédiatement d'alimenter les prompts.

---

## 18. Actions journalisées (état au 1ᵉʳ novembre 2026)

| Famille | Actions |
|---|---|
| **Authentification** | `connexion` (succès **et** refus, verrouillage inclus), `deconnexion` |
| **Documents** | `document.depot`, `document.consultation`, `document.suppression`, `document.export`, `document.resume` |
| **Corrections** | `document.correction`, `document.correction.consultation`, `document.correction.telechargement`, `document.correction.suppression` |
| **Usage de l'IA** | `assistant.question`, `traduction`, `reformulation` |
| **Administration** | `categorie.creation/modification/suppression`, `profil.maj`, `profil.mot_de_passe`, `journal.export`, `journal.purge` |
| **Refus** | `securite.refus` — jeton CSRF périmé (419) ou permission manquante (403), avec le chemin demandé |

### Ce qui est volontairement **non** tracé

Une requête **non authentifiée** rejetée en 401 n'est pas enregistrée : le
journal étant en ajout seul, un robot pourrait le noyer et enterrer les
événements réels. Seuls les refus impliquant une **session réelle** sont tracés.

### Garde-fous de la purge légale

`journal.purge` exige : administrateur technique, POST + CSRF, **mot de passe
re-saisi**, fenêtre **≤ 92 jours**, fin de période **≤ D-10**, et **5 tentatives
par 5 minutes**. La purge est elle-même journalisée, refus compris.

## 19. Durcissement du 2 novembre 2026

Cette section documente les mesures ajoutées après l'audit du 2 novembre. Elles sont **vérifiables** par des commandes, pas par des déclarations.

### 19.1 CSP par nonce — plus de script inline libre

| Avant | Après |
|---|---|
| `script-src 'self' 'unsafe-inline'` | `script-src 'self' 'nonce-<unique par requête>'` |
| `onsubmit="return confirm(…)"` dans une vue | attribut `data-confirm` traité par `workspace.js` |

Le nonce est produit par `csp_nonce()` (une fois par requête, stocké dans `$GLOBALS`) et posé sur les **trois** scripts inline : configuration `window.WORKSPACE_*`, purge du journal, et les scripts de vue. `style-src` conserve `'unsafe-inline'` pour les couleurs dynamiques (`style="border-color: …"`), ce qui est assumé et documenté.

**Vérification** : sur les 10 pages, le nombre de scripts inline égale le nombre de nonces et aucun gestionnaire `on*=` ne subsiste.

### 19.2 Négociation de contenu sur les endpoints

Un endpoint qui reçoit `Accept: application/json` ou `X-Requested-With: XMLHttpRequest` répond **toujours** en JSON. Un `fetch` ne sait pas lire un `302` : il récupère du HTML et échoue sur `response.json()`.

Trois endpoints ont été corrigés après le balayage : `categorie_save`, `categorie_delete`, `espace_actif`.

### 19.3 Protection des pages sensibles

| Page | Condition | Sans le droit |
|---|---|---|
| `/journal` | `journal.lire` | **403** + refus journalisé |
| `/administration` | au moins une permission d'administration | **403** |
| `/categories` | pouvoir déposer **ou** supprimer un document | **403** + message dédié |

Ces gardes ferment l'écart entre ce que l'interface montre et ce que le serveur autorise : plus de page qui affiche des boutons voués à un 403.

### 19.4 Comptes protégés — invariants

`utilisateur.compte_super` désigne un **ensemble** de comptes protégés.

| Invariant | Porté par | Vérifié par |
|---|---|---|
| ni suppression, ni suspension, ni rétrogradation | `changerRole`, `changerStatut`, `supprimerLogique` | `UtilisateurSuperTest` |
| ajout/retrait libres, jusqu'à zéro | `accorderSuperAdmin()`, `retirerSuperAdmin()` | `UtilisateurSuperTest` |
| trace complète | `compte.super_ajout`, `compte.super_retrait`, `compte.super_desactivation` | journal (`/journal`) |
| amorçage après effacement | `compteSuperId()` (le plus ancien) + `tools/seed_admin.php` | procédure documentée |

### 19.5 Cloisonnement des documents

`PermissionService::documentsVisibles()` calcule l'ensemble visible à partir de la **table de liaison** `document_espace` (union des espaces, confidentialité, propriété, suppression logique). Cette même fonction sert la liste, la recherche, le téléchargement et le moteur de récupération : une seule règle, un seul endroit.

Conséquence mesurée : un membre rattaché à un autre espace reçoit `[]` sur la liste et **404** sur un téléchargement par identifiant — l'existence du document n'est pas révélée.

### 19.6 CSRF sur les appels JSON

Un wrapper `fetch` (début de `workspace.js`) ajoute l'en-tête `X-CSRF-Token` lu depuis `<meta name="csrf-token">` à **tous** les appels. Côté serveur, `Csrf::validateRequest()` accepte `$_POST['_csrf']` ou l'un des en-têtes `X-CSRF-Token` / `X-XSRF-Token`.

### 19.7 Preuves exécutables

```powershell
php tools/smoke_admin_endpoints.php        # 27 vérifications, 9 endpoints
php tools/smoke_endpoints_anonymous.php    # 31 endpoints × 2 méthodes, sans session
php tools/lint_docs.php                    # structure de la documentation
php vendor/phpunit/phpunit/phpunit         # 121 tests, 281 assertions
```

Le balayage anonyme vérifie deux choses : le **statut** (401/403/405/419/422) et l'**absence de clé de données** dans le corps (`"documents"`, `"entries"`, `"chunks"`, `"url"`, `"answer"`, `"job_id"`).
