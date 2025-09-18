# AI Health Advisor Plugin

📌 **Opis**

AI Health Savetnik je WordPress/WooCommerce plugin koji koristi OpenAI da korisnicima pruži personalizovan zdravstveni izveštaj.
Na osnovu forme i pitanja (Da/Ne + intenziteti), plugin izračunava zdravstveni skor, daje detaljnu analizu, predlaže proizvode i automatski formira pakete od 2, 3, 4 ili 6 proizvoda sa popustima i objašnjenjem.

## ⚙️ Funkcionalnosti

- Forma povezana sa WooCommerce checkout poljima (autosave).
- Dinamična pitanja (Da/Ne, podpitanja, intenzitet).
- Izračunavanje zdravstvenog skora i kategorizacija (Odlično, Dobro, Umereno, Rizično).
- AI analiza i prirodne preporuke (OpenAI integracija).
- Povezivanje pitanja sa WooCommerce proizvodima.
- Metabox na proizvodu: „Zašto je dobar" i „Doziranje".
- Automatsko kreiranje paketa od 2, 3, 4 ili 6 proizvoda:
  - Regularni popusti (npr. 10%, 12%, 16%, 20%).
  - Dodatni VIP popust za registrovane VIP korisnike.
- Admin podešavanja: API ključ, popusti, boje progress bara, kategorije skora.
- Izveštaji sa CSV/PDF eksportom.

## 📂 Struktura projekta

```
/ai-health-savetnik
│── ai-health-savetnik.php         # Glavni loader
│── /admin
│    ├── class-aihs-admin.php      # Admin meni i podešavanja
│    ├── class-aihs-questions.php  # CPT pitanja
│    ├── class-aihs-products.php   # Metabox za proizvode
│    ├── class-aihs-packages.php   # Logika paketa i popusta
│    └── views/                    # Admin template fajlovi
│── /frontend
│    ├── class-aihs-form.php       # Forma i autosave
│    ├── class-aihs-quiz.php       # Pitanja i odgovori
│    ├── class-aihs-results.php    # Rezime i skor
│    └── class-aihs-display.php    # Analiza i prikaz paketa
│── /includes
│    ├── class-aihs-rest.php       # REST API rute
│    ├── class-aihs-db.php         # Custom tabela i modeli
│    └── helpers.php
│── /assets
│    ├── css/
│    └── js/
│── /languages
│    └── ai-health-savetnik.pot
```

## 🔧 Instalacija

1. Kloniraj repozitorijum u `wp-content/plugins/`:
   ```bash
   git clone https://github.com/tvoj-repo/ai-health-savetnik.git
   ```

2. Aktiviraj plugin u WordPress adminu.

3. U podešavanjima unesi OpenAI API ključ.

4. Konfiguriši pitanja, proizvode i popuste u adminu.

## 🧑‍💻 Razvoj

### Branch strategija
- `main` → stabilna verzija
- `develop` → aktivni razvoj
- `feature/*` → pojedinačne funkcionalnosti

### Commits (primer)
- `feat: init plugin structure`
- `feat: add CPT for questions`
- `feat: implement AI analysis with OpenAI`
- `feat: package creation (2,3,4,6 products)`
- `fix: sanitize REST inputs`

### Workflow
1. Kreirati GitHub Issue za novu funkcionalnost.
2. Raditi na posebnoj `feature/` grani.
3. Push-ovati redovne commit-e sa jasnim opisima.
4. Otvoriti Pull Request → code review → merge u `develop`.
5. Kada verzija bude spremna → merge u `main` i tagovati release (v1.0.0).

## 📊 Izveštaji

- Pregled svih popunjenih upitnika (ime, email, skor, datum).
- CSV/PDF eksport uključuje: pitanja i odgovore, analizu, preporuke, predložene pakete.

## 🔒 Bezbednost

- REST rute zaštićene nonce-om i capability proverama.
- Validacija i sanitizacija inputa.
- GDPR podrška (saglasnost korisnika, pravo na brisanje podataka).

## 🌍 Internacionalizacija

- gettext podrška (.pot fajl).
- RTL i ARIA kompatibilnost.

## 📜 Licenca

MIT License.
Slobodno koristi, menja i deli.

## 🚀 Plan za naredne verzije

- [ ] Dodavanje vizuelnog kreatora pitanja (drag & drop).
- [ ] E-mail notifikacije sa izveštajem korisniku.
- [ ] Widget za health-score progress bar.
- [ ] API endpoint za integraciju sa mobilnim aplikacijama.

---

## 📋 Detaljne Specifikacije

### 1) Svrha i cilj
Plugin služi kao AI savetnik za zdravlje povezan sa OpenAI-om. Korisnik popunjava formu (isti set polja kao WooCommerce checkout), prolazi kroz binarna pitanja (Da/Ne) sa mogućim podpitanjima/intenzitetima, dobija:
- ukupni zdravstveni skor (što je više „Da" ⇒ lošiji skor),
- sažetak svih odgovora,
- detaljnu analizu sa prirodnim preporukama,
- predlog proizvoda (po pitanju), doziranje, i pakete (5–6 proizvoda sa popustom).

### 2) Integracije i tehnički okvir
- WordPress 6.x, WooCommerce 8.x+.
- PHP 8.1+, WP REST API, WP Nonces, Capabilities.
- OpenAI (serverside poziv, API ključ unet u adminu).
- Poželjno: ACF (za dodatne atribute proizvoda/doziranja), ali plugin mora raditi i bez ACF-a (sopstveni metaboxovi).

### 3) Podaci i modeli

#### 3.1. Polja forme (mapiranje na WooCommerce):
- Ime → billing_first_name
- Prezime → billing_last_name
- Email → billing_email
- Telefon → billing_phone
- Adresa → billing_address_1
- Grad → billing_city
- Poštanski broj → billing_postcode
- Država → billing_country
- (Opciono) godina starosti, pol → user_meta (custom)

**Autosave:** svako polje se čuva:
- serverski (AJAX / REST, status „draft" zapisa upitnika vezan za user_id ili session_id),
- lokalno (localStorage) kao fallback; pri obnovi sesije sync-uje se sa serverom.

#### 3.2. Pitanja
**CPT:** health_question

**Polja:**
- Tekst pitanja
- Tip: binarno (Da/Ne)
- Podpitanje (pojavljuje se samo ako je odgovor „Da")
- Dodatni izbori/intenzitet (npr. 1–3)
- Težina (poeni za skor npr. Da=10, intenziteti +5/+10/+15)
- Preporučeni proizvodi (lista WC product ID)
- Napomena za AI (hint konteksta)
- Redosled pitanja: ručno drag&drop ili polje „priority".

#### 3.3. Rezultati (odgovori)
**Tabela:** wp_aihs_responses
- id, user_id/session_id, created_at, updated_at
- JSON polje sa svim odgovorima (pitanje→odgovor→intenzitet)
- Izračunat skor (0–100), kategorija (npr. Odlično/Dobro/Umereno/Rizično)

#### 3.4. Proizvodi, doziranje, paketi
Koristi postojeće WooCommerce proizvode.

**Za svaki proizvod:**
- Polja: „Zašto je dobar" (kratki opis za AI), „Doziranje" (tekst), „Tag: Zdravstveni" (filter).
- Mapiranje pitanja→proizvodi čuva se u health_question.

**Paketi:**
- Dinamički „bundle" (virtuelni) koji spaja 5–6 proizvoda.
- Pravila popusta (%, fiksno, stepenasto).
- Cena paketa = suma komponenti – popust (propagira se u korpu/checkout).

### 4) Admin interfejs

#### 4.1. Opšta podešavanja
- OpenAI API ključ (secure, masked).
- Temperatura/model, maksimalna dužina odgovora.
- Tekstovi za UI (naslovi, opisi, boje progres bara).
- Skala skora: max=100; mapiranje u kategorije i boje progress bara.
- Pravila popusta za pakete (globalno + override po paketu).

#### 4.2. Builder pitanja
- Lista pitanja (pretraga, filter po tagu).
- Uređivanje jednog pitanja:
  - Tekst, težine, podpitanja, intenziteti.
  - Izbor preporučenih proizvoda (multi-select iz WC kataloga).
  - Hint za AI.
  - Pregled „Simulacija skora" (test).

#### 4.3. Doziranje i „Zašto je dobar"
**Metabox na WC proizvodu:**
- „Zašto je dobar" (maks 400–600 karaktera).
- „Doziranje" (plain text/HTML).
- Checkbox „Dozvoli u AI preporukama".

#### 4.4. Paketi
**Ekran „AI Paketi":**
- Kreiraj paket → odaberi 5–6 proizvoda → definiši popust → generiši WC product (virtual, grouped/bundle).
- Auto-opis paketa iz sastava i koristi (AI može da generiše finalni marketing opis; admin ga potvrđuje).

#### 4.5. Izveštaji i eksport
- Lista popunjenih upitnika: ime, email, skor, datum.
- CSV/PDF eksport (PDF sažetak: skor, odgovori, analiza, preporuke, doziranja).

### 5) Frontend tok

#### Forma (korisnik nije ulogovan)
- Prikaži polja (mapirana na WooCommerce). Autosave (svaki blur). „Nastavi" vodi na pitanja.
- Ako je korisnik ulogovan, auto-popuni polja iz user meta; pri prvom unosu sinhronizuj i popuni WP korisnički profil (billing/shipping) — bez kreiranja porudžbine.

#### Pitanja
- Jedno po jedno ili blok po izboru admina.
- Odgovor „Da" → prikaži podpitanje/intenzitete.
- Autosave posle svakog klika.

#### Rezime i skor
- Izračunaj skor: 100 – Σ(težine), floor na min 0.
- Prikaži progress bar + kategoriju boje.
- Prikaži tabelu pitanja/odgovora (čitko).

#### AI analiza i preporuke
- Serverski poziv ka OpenAI sa: pol (ako postoji), godine (ako postoji), lista odgovora, skor, omogućeni proizvodi sa „Zašto je dobar" + „Doziranje".
- Vraća: umerena, razumljiva analiza + prirodni saveti.

#### Proizvodi i paketi
- Sekcija „Preporučeni proizvodi": kartice proizvoda (opis „Zašto je dobar", doziranje).
- Sekcija „Preporučeni paketi": 1–3 paketa (5–6 proizvoda) sa jasno istaknutim popustom i CTA „Dodaj u korpu".
- Dodavanje u korpu prenosi kompletnu cenu paketa u korpu i checkout (bez raspada na pojedinačne cene, osim ako je admin dozvolio prikaz komponenti).

### 6) Skor — algoritam (default, podesiv)
- Svako „Da" dodaje osnovnu težinu (npr. 10).
- Intenzitet 1/2/3 dodaje dodatnih 5/10/15.
- Skor = max(0, 100 – suma_težina).

**Kategorije (podesive):**
- 80–100: Odlično
- 60–79: Dobro
- 40–59: Umereno
- 0–39: Rizično

### 7) Preporuke i paketi — logika
- Pitanje može imati 0–N preporučenih proizvoda (ručno mapiranje u adminu).
- Na kraju: skup svih „pogodaka" se normalizuje (ukloni duplikate).
- Ako ukupno ≥5 proizvoda: kreiraj 1–3 kombinacije po temama (npr. „Detoks", „Imunitet", „Digestivni balans"), svaki 5–6 proizvoda.
- Popust: npr. 10% za paket (podesivo) + opcioni stepenasti popust po dodatnom proizvodu.
- Cena paketa u korpi = suma komponenti – popust (propagira se na checkout).

### 8) Bezbednost, performanse, privatnost
- REST rute za autosaves/rezultat zaštićene nonce-om, korisničkim capabilities; session_id za goste (cookie).
- Sanitizacija i validacija svih polja.
- Throttling/OpenAI error fallback (prikaži korisniku „Analiza je privremeno nedostupna — pokušajte ponovo" i sačuvaj rezultate).
- GDPR: Checkbox saglasnosti i link ka politici privatnosti; mogućnost brisanja zapisa po zahtevu.

### 9) I18N i pristupačnost
- Potpuna i18n (gettext), RTL-friendly, ARIA za formu i progress bar.

### 10) Dev detalji (skraćeno)

#### Custom tabele
- wp_aihs_responses (vidi 3.3).

#### Ključne REST rute
- POST /aihs/v1/autosave — čuva formu/odgovore (user/session scoped).
- POST /aihs/v1/finish — zaključava upitnik, računa skor, vraća ID zapisa.
- POST /aihs/v1/analyze — poziva OpenAI i vraća analizu + preporuke.
- POST /aihs/v1/bundle — generiše/obnavlja paket i vraća WC product id.

#### Hookovi
- woocommerce_add_to_cart — za pakete: setuj ukupnu cenu na osnovu pravila.
- user_register / prvi unos VIP/forme — sinhronizacija billing/shipping polja.
- save_post_product — validacija polja „Zašto je dobar" i „Doziranje".

#### UI/UX
- Stepper (Forma → Pitanja → Rezime → Analiza → Proizvodi/Paketi).
- Sticky CTA „Nastavi / Sačuvaj"; vidljiv indikator autosave-a.
- Progress bar u boji prema kategoriji.