# État de démonstration — produit par la recette du 2 novembre 2026

🇬🇧 English version: [ETAT_DE_DEMO.md](ETAT_DE_DEMO.md)

> **Objet.** Ce fichier décrit l'état exact sur lequel reposent la vidéo et le jury.
> Il n'a **pas** été écrit à la main : c'est l'état que la recette a produit, puis
> relu dans la base. Si cet état change, ce fichier change dans le même commit —
> sinon la démonstration ne correspond plus à ce qui est décrit.
>
> Chaque chiffre ci-dessous a été interrogé le 2 novembre 2026, après la recette.

---

## 1. Comptes — 2, dont 1 protégé

| # | Adresse | Rôle global | Protégé | Adhésions |
|---|---|---|---|---|
| 1 | `anatolekbore@gmail.com` | administrateur_technique | **★ oui** | Général (responsable) · Chantier test (responsable) |
| 2 | `membre.recette@example.org` | membre | non | Chantier test (**contributeur**) |

**Les mots de passe ne sont pas dans ce dépôt** — il est public. Celui du compte
protégé se régénère avec `php tools/seed_admin.php --reset`.

Le second compte existe pour jouer le membre dans la vidéo : c'est lui qui montre
ce que voit réellement un non-administrateur.

---

## 2. Espaces de travail — 2

| # | Nom | Visibilité | Statut | Documents |
|---|---|---|---|---|
| 1 | **Général** | restreint | actif | **4** |
| 2 | **Chantier test** | restreint | actif | **2** |

Les deux espaces contiennent des ensembles **différents** à dessein : c'est ce qui
rend le cloisonnement visible à l'écran.

---

## 3. Documents — 4, un par nature

| # | Titre | Type | Langue | Espaces | Chunks | Ce que ça prouve |
|---|---|---|---|---|---|---|
| 1 | Note chantier Koudougou | texte | fr | Général + Chantier test | 1 | classement multi-espaces |
| 2 | Rapport scanné Koudougou | PDF | fr | Général + Chantier test | 4 | PDF scanné avec couche de texte |
| 3 | **Doc mooré scanné** | PDF | **mo** | Général | 2 | OCR d'un scan mooré, 1 093 caractères |
| 4 | **Meeting Validation** | audio (wav) | **en** | Général | 1 | **la voix devient source**, indexée en 9 s |

Transcription du document 4 :

> *Welcome to this meeting. The budget approved by the donor for the school
> construction is 10 million CFA francs.*
> `[Traduction — French] Bienvenue à cette réunion. Le budget approuvé par le
> bailleur pour la construction de l'école est de 10 millions de francs CFA.`

---

## 4. Ce que voit chaque profil — vérifié

| Contrôle | anatole | membre.recette |
|---|---|---|
| `/documents` — documents visibles | **4** | **2** *(documents 1 et 2)* |
| `/journal` | **200** | **403** |
| `/administration` | **200** | **403** |
| Déposer dans Chantier test | oui | oui *(contributeur)* |
| Supprimer un document | oui | **non** |
| Question sur le budget de l'école | **répond** avec l'enregistrement | **refuse** — l'enregistrement n'est pas dans son espace |

Les deux documents du membre sont exactement ceux classés dans Chantier test. Rien
de Général ne fuit, et le refus est explicite plutôt qu'une réponse fausse.

---

## 5. Scénario de démonstration — les dix gestes et ce qui doit apparaître

| # | Geste | Attendu |
|---|---|---|
| 1 | Page d'accueil | le produit, pas une diapositive |
| 2 | **Enregistrer 3 s, déposer** | statut *indexed* en **≈9 s** |
| 3 | Poser la question sur l'enregistrement | **10 millions de francs CFA**, source = l'audio |
| 4 | Poser la question sur le document mooré | la réponse vient du scan mooré |
| 5 | Tâche agentique sur deux documents | plan · récupération · rédaction · vérification, puis un résultat structuré |
| 6 | Nettoyer, puis exporter | la réponse sans échafaudage |
| 7 | Résumer un document | un résumé avec sa source |
| 8 | Changer d'espace, reposer la même question | **refus honnête** : *« Je ne trouve pas cette information dans l'espace de travail courant »* |
| 9 | Session membre | 2 documents, `/journal` en 403 |
| 10 | Journal des appels | point d'entrée, modèle, statut, durée — en direct |

---

## 6. Avant l'enregistrement

```powershell
# 1. L'état correspond à ce document
C:\wamp64\bin\mysql\mysql8.4.13\bin\mysql.exe -u root -D ai_knowledge_workspace ^
  -e "SELECT COUNT(*) FROM documents WHERE supprime_le IS NULL;"   # 4

# 2. La suite est verte
php vendor/phpunit/phpunit/phpunit                                 # 121 tests

# 3. La chaîne tient
php tools/prove_provenance.php 4

# 4. Le journal d'appels est lisible en direct
Get-Content storage\logs\model_calls.log -Tail 3
```

Puis **`Ctrl + F5`** sur chaque page utilisée. Un onglet périmé est la première
cause d'un geste qui « ne marche pas ».

---

## 7. Ce que la recette a prouvé

**Dix défauts** ont été trouvés en **faisant les gestes**, pas en lisant le code.
Tous corrigés et consignés dans `REGISTRE_PROBLEMES.md` (P-51 à P-60), dont quatre
qui auraient été visibles à la caméra :

| Défaut | Ce que ça donnait à l'écran |
|---|---|
| P-52 | le formulaire annonçait *« Général — 0 document »* alors que Général en contenait deux |
| P-58 | l'espace affiché en haut ne limitait pas la recherche |
| P-59 | une réponse confiante sur le **mauvais** chantier, sans refus |
| P-60 | le bouton *Résumer* ne faisait rien |

Cette liste vaut mieux qu'un rapport propre : c'est la preuve que l'état ci-dessus
a été **mesuré**, pas imaginé.

---

## 8. Ce que cet état ne contient volontairement pas

| Absent | Pourquoi |
|---|---|
| Un espace vide | le refus (geste 8) prouve le cloisonnement mieux qu'un filtre vide |
| Un espace « public interne » | le cas privé est la démonstration la plus forte |
| Les documents de test supprimés logiquement | exclus de toutes les requêtes par conception |
| Les identifiants | le dépôt est public |

---

*État produit et vérifié le 2 novembre 2026 pendant la recette (`RECETTE_AZ.fr.md`).
À relire contre le §4 avant chaque enregistrement.*
