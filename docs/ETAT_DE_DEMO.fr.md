# État de démonstration — figé le 2 novembre 2026

🇬🇧 English version: [ETAT_DE_DEMO.md](ETAT_DE_DEMO.md)

> **⚠ Base remise à zéro le 2 novembre 2026.** Ce document décrit l'état **avant** la remise à zéro. Il sera réécrit dès que la recette (`RECETTE_AZ.fr.md`) aura produit un état stable.

> **Objet.** Ce fichier décrit l'état exact de la base sur lequel reposent la vidéo
> et le jury. Si cet état change, ce fichier doit être mis à jour dans le même
> commit — sinon la démonstration ne correspond plus à ce qui est décrit.
>
> Chaque chiffre ci-dessous a été lu dans la base le 2 novembre 2026, jamais de
> mémoire.

> 
---

## 1. Comptes — 3 actifs, 2 protégés

| # | Adresse | Nom | Rôle global | Protégé | Adhésions |
|---|---|---|---|---|---|
| 1 | `anatolekbore@gmail.com` | Anatole KABORE | administrateur_technique | **★ oui** | Général (responsable) · Espace demo (responsable) · Projet 001 (responsable) |
| 6 | `admin@super.bf` | Admin Super | administrateur_technique | **★ oui** | Général (responsable) |
| 4 | `membre.demo@local` | Membre Demo | membre | non | Espace demo (**lecteur**) · Projet 001 (**contributeur**) |

**Les mots de passe ne sont volontairement pas écrits dans ce dépôt.** Il est
public. Celui du compte protégé peut être régénéré à tout moment :

```powershell
php tools/seed_admin.php --reset
```

La commande l'affiche une fois et ne stocke qu'une empreinte Argon2id.

> **Pourquoi deux comptes protégés ?** Pour montrer le modèle : la protection est
> un **ensemble**, pas un siège unique. L'un ou l'autre peut récupérer
> l'installation.

---

## 2. Espaces de travail — 3

| # | Nom | Visibilité | Statut | Documents actifs |
|---|---|---|---|---|
| 1 | **Général** | restreint | actif | **9** |
| 3 | **Espace demo (cloisonnement)** | restreint | actif | 0 |
| 5 | **Projet 001** | restreint | actif | 0 |

Les espaces 3 et 5 sont vides **exprès** : ils prouvent qu'un membre rattaché à
un autre espace ne voit rien de « Général ».

---

## 3. Adhésions — 6

| Compte | Espace | Rôle local | Ce que cela accorde |
|---|---|---|---|
| anatole | Général | responsable | lire, déposer, tâcher, supprimer, modérer |
| anatole | Espace demo | responsable | idem, sur un espace vide |
| anatole | Projet 001 | responsable | idem, sur un espace vide |
| admin@super.bf | Général | responsable | lire, déposer, tâcher, supprimer, modérer |
| membre.demo | Espace demo | **lecteur** | lecture seule |
| membre.demo | Projet 001 | **contributeur** | lire, déposer, tâcher |

---

## 4. Documents — 9 actifs

| # | Titre | Catégorie | Confidentialité | Espace | Type |
|---|---|---|---|---|---|
| 13 | Formation | Formation | restreint | Général | PDF |
| 17 | Audio | Chants | restreint | Général | audio |
| 18 | Scan | Rapports | restreint | Général | image (OCR) |
| 19 | chant1 | Chants | restreint | Général | PDF scanné |
| 26 | Meeting2 | Rapports | restreint | Général | audio transcrit |
| 27 | Meeting3 | Rapports | restreint | Général | audio transcrit |
| 28 | Meeting4 | Rapports | restreint | Général | audio transcrit |
| 29 | Meeting5 | Rapports | restreint | Général | audio transcrit |
| 30 | Meeting6 | Formation | restreint | Général | audio transcrit **+ 1 correction** |

Volumes : **34 versions**, **2 732 chunks indexés**, **1 correction** (document 30).

### Ce qui ne fait pas partie de cet état

- **26 documents sont supprimés logiquement** (`supprime_le` renseigné) — résidus
  des audits. Ils sont invisibles partout (liste, recherche, récupération,
  téléchargement) mais toujours présents en table. Sans conséquence ; à purger
  avec le `DELETE` ci-dessous si une base propre est souhaitée.
- **11 tâches d'ingestion**, toutes `termine`. Aucune en attente.

---

## 5. Catégories — 5

`Rapports` · `Procédures` · `Formation` · `Administratif` · `Chants`

---

## 6. Ce que voit chaque profil — résultats attendus

Valeurs à contrôler à l'écran avant d'enregistrer. Toute différence signifie que
l'état a dérivé.

| Contrôle | anatole | admin@super.bf | membre.demo |
|---|---|---|---|
| `/documents` — lignes | **9** | **9** | **0** |
| Filtre « Général » | 9 | 9 | 0 |
| Filtre « Projet 001 » | 0 | 0 | 0 |
| `/journal` | **200** | **200** | **403** |
| `/administration` | **200** | **200** | **403** |
| `/categories` | 200 | 200 | 200 *(contributeur)* |
| `/espaces` — espaces listés | 3 | 1 | 2 |
| API `/api/documents.php` | 9 | 9 | `[]` |
| Téléchargement d'un document de « Général » | 200 | 200 | **404** |
| Entrées de menu visibles | 10 | 10 | 6 |

Menu d'un membre simple : Question · Traduction · Documents indexés · Mon profil ·
Espaces — les entrées d'administration sont **masquées** et **refusées**.

---

## 7. Scénarios de démonstration — 8 gestes

Chaque ligne indique le geste et **ce qui doit apparaître**, pour qu'un échec soit
visible immédiatement plutôt qu'au montage.

| # | Geste | Où | Résultat attendu |
|---|---|---|---|
| 1 | Poser une question sur les rapports | `/` | Réponse + citations numérotées vers des documents de « Général » |
| 2 | Demander une synthèse | `/tache` | Document structuré, sources listées, exports actifs |
| 3 | Traduire un extrait français en mooré | `/traduire` | Traduction + panneau des extraits utilisés |
| 4 | Montrer la liste des documents | `/documents` | 9 lignes, badges d'espaces, couleurs de catégories |
| 5 | Filtrer sur « Espace demo » | `/documents` | **0 ligne** et l'état vide — preuve du cloisonnement |
| 6 | Ouvrir la modale cadenas sur « Meeting6 » | `/documents` | Modale espace + confidentialité, mot de passe exigé |
| 7 | Ouvrir les onglets d'administration | `/administration` | 3 onglets avec leurs compteurs : 3 comptes, 3 espaces, 6 adhésions |
| 8 | Se connecter en `membre.demo` | `/login` | Menu de 6 entrées, `/documents` vide, `/journal` en 403 |

Facultatif si le temps le permet : supprimer un document (fonctionne), ou
accorder/retirer la protection par l'icône couronne (journalisé).

---

## 8. Liste de contrôle avant tournage

```powershell
# 1. Le serveur répond
curl.exe -s -o NUL -w "%{http_code}" http://127.0.0.1:8080/login      # 200

# 2. L'état correspond à ce document
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL;"       # 9

# 3. La protection est en place
php tools/seed_admin.php                                                # 2 comptes protégés

# 4. La suite est verte
php vendor/phpunit/phpunit/phpunit                                      # 121 tests
```

Puis, dans le navigateur : **`Ctrl + F5`** sur chaque page utilisée pendant
l'enregistrement — l'application envoie `no-store`, mais un onglet périmé reste la
première cause d'un geste qui « ne marche pas ».

---

## 9. Revenir à cet état exact

Si un geste de démonstration modifie l'état, voici comment revenir.

```sql
-- Les 9 documents appartiennent à « Général »
UPDATE documents SET supprime_le = NULL WHERE id IN (13,17,18,19,26,27,28,29,30);

-- Chaque document est classé dans Général uniquement
DELETE FROM document_espace WHERE document_id IN (13,17,18,19,26,27,28,29,30);
INSERT INTO document_espace (document_id, espace_id)
SELECT id, 1 FROM documents WHERE supprime_le IS NULL;

-- Confidentialité par défaut
UPDATE documents SET confidentialite = 'restreint' WHERE supprime_le IS NULL;

-- Espaces ouverts
UPDATE espace SET statut = 'actif';
```

```powershell
# Les deux comptes protégés
php tools/seed_admin.php --grant=anatolekbore@gmail.com
php tools/seed_admin.php --grant=admin@super.bf
```

---

## 10. Ce que cet état ne contient volontairement pas

| Absent | Pourquoi |
|---|---|
| Espace « public interne » | la règle de visibilité est démontrée par le cas **privé**, plus fort |
| Adhésion expirée | compliquerait l'explication sans apporter de preuve |
| Document supprimé dans la liste | la liste doit rester stable pendant l'enregistrement |
| Identifiants personnels dans git | le dépôt est public |

---

*État figé le 2 novembre 2026 — vérifier le §6 avant chaque enregistrement.*
