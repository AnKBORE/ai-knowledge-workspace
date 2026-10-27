# Sécurité et confidentialité des données — API d'inférence

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
- [`docs/GUIDE_DE_REALISATION.md`](GUIDE_DE_REALISATION.md) — architecture et sécurité du projet.
