<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Compact Mooré (Mossi) linguistic reference injected into translation prompts.
 *
 * Compiled from: Niggli, U. — Dictionnaire Mooré-Français-English (SIL, 10 650
 * entries), Peace Corps "An Introduction to the Moore Language" (2006), and
 * the Association Montivilliers-Nasséré French–Mooré lexicon.
 *
 * Kept as one heredoc so it can be appended verbatim to a system prompt.
 */
final class MooreReference
{
    public static function prompt(): string
    {
        return <<<'MOORE'
## Mooré (Mossi) reference — use for ALL translations involving Mooré

### Orthography (Burkina national alphabet)
- Alphabet: a b d e ɛ f g h i ɩ k l m n o p r s t u ʋ v w y z (25 letters).
- ɩ is a close near-front vowel (between i and e); ʋ is a close near-back vowel (between u and o). Never replace them with i/u.
- Nasal vowels carry a tilde: ã ẽ ĩ õ ũ. For long nasal vowels only the first is marked (ãa, ẽe).
- Long vowels are doubled: aa ee ɛɛ ii ɩɩ oo uu ʋʋ.
- Tones are NOT written.
- -ã (short form of wã) is the definite article suffix: koomã "the water", biigã "the child".
- -me / -ame is the declarative clause-final marker attached to the verb: waame "came", yeelame "said".

### Pronouns
mam = I/me · fo (often reduced to f) = you sg · a = he/she/it, also his/her/its · tõnd / tũ = we · yãmb / y = you pl · bãmb / b = they/them.
"a" is also placed before proper nouns and before numerals: a Poll "Paul", a yiibu "two".

### Grammar essentials
- Word order SVO. Modifiers follow the noun.
- Negation: preverbal ka or pa + clause-final ye → "Mam ka mi ye" (I don't know), "Mam pa wumd ye" (I don't understand).
- Coordinators: tɩ and la "and", bɩ "or".
- Question words: bõe "what/why", wana / a wana "how much/how", yɛ sentence-final where/yes-no particle, yĩnga "here/how".
- No subject-verb agreement; tense is preverbal, aspect by suffixes.

### Greetings and everyday expressions (high frequency — translate literally)
- Ne y yibeoogo! = Good morning (to several people). Ne y wĩndga! = Good day. Ne y zaabré! = Good evening. Ne y yũngo = Good night.
- Yibeoog kibaré? = How was your morning? · Wĩndg kibaré? = How is your day? · Zaabr kibaré? = How is your evening?
- (La y) zak rãmba? / F zak ramba? = How are the people of your home/family? — zak = family compound, rãmba = people. Answer: "B kiémame / B yaa laafi" (they are well).
- Laafi bala = very well, fine (standard reply to greetings). Yel ka ye = no problem.
- Barka / barka wusgo = thank you / thank you very much. Pa sek ye = you're welcome.
- Mam yʋʋr la a X = my name is X. Fo yʋʋr la a bõe? = what is your name?
- Ne y kena = welcome. Bilfu = see you soon. Wẽnd na kõ-d nindaare = goodbye.
- Y gafara = excuse me. Mam sugri = sorry. Song y mam! = help me!
- Mam wumdame = I understand. Mam ka mi ye = I don't know.

### Core vocabulary
People/family: ned person, neba people, rãmba people/inhabitants, pʋga woman/wife, sao man/husband, biiga child, baaba father, ma mother, poore sibling, zak home/family compound, roogo house/hut, tẽnga village/country/earth.
Speech: goma to speak/language, gomde speech, yeele to say, wilgi to show/tell, togse to announce, wumda to hear/understand, boole to call, bãnga to know.
Work/time: tũuma work, kibare news/how-things-are, tũnug to use, zaamẽ yesterday, dãa market/day, beoogo tomorrow/morning, wakat time, yʋʋmde year, daare day.
Verbs: wa come, kẽnga go, maane do/make, nonga like/love, mi know, gese see, rɩɩ take/eat, paame get/receive, tũus begin, baase finish, yɩɩ be/become, kẽeme be well/exist.
Objects/abstract: ligdi money, rɩɩbo food, koom water, sɛba writing/book, sõma good, beoogo good, sũurã heart/mind, yam wisdom/mind, noore mouth/door.
Religion (common in documents): Wẽnnaam / Wend God, misa Mass, karen-saamba teacher/catechist, KOMINYÕ communion, resgo/yelle song.

### Numerals
a yembre 1 · a yiibu 2 · a tãabo 3 · a naase 4 · a nu 5 · a yoobe 6 · a yopoe 7 · a nii 8 · a wɛ 9 · piiga 10 · pisi 20 · pis-tãa 30 · kẽmse 100 · tusri 1000.

### Translation rules
- Mooré speech recognition transcripts are approximate: reconstruct probable standard forms first (e.g. "Ibe kibali" → "Yibeoog kibaré", "f zak ramba" → "fo zak rãmba"), THEN translate.
- French loanwords are frequent and stay close to French (misiridi, lɛɛr, tẽn-tʋmd, kaset).
- Output standard orthography (ɩ ʋ ã ẽ ĩ õ ũ ñ), never an ASCII approximation.
MOORE;
    }
}
