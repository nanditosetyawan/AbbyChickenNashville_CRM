# Dokumentasi Bedah Desain UI/UX & Motion: Aplikasi "Baby Name Match"

Dokumen ini adalah pembedahan menyeluruh (*end-to-end breakdown*) dari konsep desain aplikasi mobile **Baby Name Match ("One Deck** $\cdot$ **Two Votes")** karya studio desain **Purrweb**. Dokumen ini disusun **berdasarkan Halaman Layar Mobile (Screen-by-Screen / State-by-State)** sehingga pembaca dapat membayangkan tata letak, atmosfer visual, interaksi, hingga pergerakan animasi secara utuh tanpa perlu melihat video aslinya.

## BAGIAN 1: IDENTITAS PROYEK & SISTEM DESAIN GLOBAL (GLOBAL DESIGN SYSTEM)

Sebelum masuk ke masing-masing halaman layar, berikut adalah fondasi visual dan ciri khas utama yang mengikat keseluruhan aplikasi.

### 1. Konsep & Fungsi Aplikasi

* **Nama Konsep:** Baby Name Match (`ONE DECK · TWO VOTES`).

* **Tujuan Utama:** Aplikasi kolaboratif untuk pasangan (dalam studi kasus ini: **You** & **Ava**, dengan nama keluarga **Hale**) dalam memilih nama bayi. Kedua pasangan melakukan *swipe* pada satu tumpukan kartu (*deck*) yang sama dari ponsel masing-masing, lalu sistem secara otomatis mencocokkan nama yang disukai keduanya (*match*), menguji kecocokan fonetik dengan nama belakang keluarga, dan menyimpannya ke dalam daftar favorit bersama (*Shortlist*).

### 2. Tema & Gaya Desain (Design Style)

* **Tema Utama:** *Warm Organic Editorial & Tactile Modernism*.

* **Nuansa (Mood):** Hangat, tenang, dewasa, organik, dan intim. Berbeda dengan aplikasi bayi konvensional yang menggunakan warna pastel kekanak-kanakan (biru muda/pink permen), desain ini menggunakan pendekatan **Earthy Botanical** (hijau hutan, hijau daun, krem kertas hangat, dan aksen terakota).

* **Gaya Komponen:** *Bento-card layout* dengan sudut membulat besar (*high border-radius* sekitar $24\text{ px}$ hingga $32\text{ px}$), dipadukan dengan tipografi kontras tinggi bergaya majalah (*editorial typography*).

### 3. Palet Warna Lengkap (Color Palette)

| Nama Warna | Estimasi Kode Hex | Penggunaan pada Komponen UI | 
| ----- | ----- | ----- | 
| **Warm Alabaster / Soft Cream** | `#F4F3EC` | Latar belakang utama layar aplikasi (*app background*). | 
| **Pure Card Cream** | `#FAF9F5` | Latar belakang kartu nama utama (*Swipe Card*) & *Bottom Sheet*. | 
| **Soft Sand / Stone Grey** | `#EAE9E0` | Kontainer sekunder (bar filter, kartu grafik popularitas). | 
| **Deep Forest Green** | `#23331D` | Kartu *header* status pasangan, kartu *Match* utama, dan kartu *Shortlist* terbaru. | 
| **Muted Olive Green** | `#3A4633` | Kartu item *Shortlist* urutan kedua dan ketiga. | 
| **Sage Bark** | `#6E7865` | Kartu item *Shortlist* yang terlipat (*collapsed*) & teks sekunder. | 
| **Vibrant Leaf Green (Primary Accent)** | `#6CC24A` | Tombol aksi utama (*Swipe together*, *Like*, tab aktif, tombol audio, lencana angka). | 
| **Terracotta / Burnt Orange** | `#C85A32` | Aksen sorotan penting: peringkat popularitas (`#24`, `#71`), bintang rating, tren naik ($\uparrow 6$), dan efek cincin cahaya *Match*. | 
| **Deep Charcoal / Forest Black** | `#182015` | Warna teks utama (judul nama bayi yang berukuran raksasa). | 

### 4. Tipografi & Ciri Khas Visual (Signature Characteristics)

* **Kontras Ukuran Ekstrem:** Nama bayi (*"Aurora"*, *"Wren"*) ditampilkan dalam ukuran sangat besar ($48\text{ pt} - 56\text{ pt}$, *Bold Sans-Serif* dengan sedikit sentuhan humanis/organik) sehingga langsung menjadi pusat perhatian.

* **Fitur "Family Last Name Test" (Ciri Khas Unik):** Aplikasi tidak hanya menampilkan nama depan, tetapi langsung menggabungkannya dengan nama belakang keluarga (**Hale**), lengkap dengan panduan cara baca fonetik (`uh-ROR-uh HAYL`, `REN HAYL`) dan uji kecocokan bunyi (*"passes the backyard test"*, *"soft with Hale"*).

* **Indikator Kolaborasi Real-Time:** Penggunaan dua avatar lingkaran yang saling bertumpuk (**`Y`** berwarna hijau terang `#6CC24A` untuk *You*, dan **`A`** berwarna hijau tua `#2E5A27` untuk *Ava*) yang selalu muncul pada komponen sinkronisasi pasangan.

* **Kanvas Presentasi Eksternal (Luar Layar HP):** Di sekeliling bingkai iPhone 15/16 Pro, terdapat latar belakang hijau lumut gelap (`#3D472C`) dengan elemen dekoratif melayang: pil filter `Girl · Classic · 2 origins` di kiri, dua tuts menyerupai *keycap* mekanikal tiga dimensi bertuliskan **`[ A ]`** dan **`[ M ]`** di bawah, serta kartu pratinjau kecil **`Aurora Hale`** di kanan bawah.

## BAGIAN 2: BEDAH HALAMAN DEMI HALAMAN (SCREEN-BY-SCREEN DEEP DIVE)

Secara keseluruhan, alur aplikasi di dalam video terbagi menjadi **4 Halaman (Layar/State Utama)**. Berikut adalah rincian lengkap setiap halaman.

### HALAMAN 1: Layar Utama "Deck" (Eksplorasi & Pemilihan Nama)

* **Rentang Waktu Kemunculan:** `00:00 – 00:01` (Awal), `00:06 – 00:07` (Setelah Match ditutup), dan `00:14 – 00:15` (Kembali dari Detail Nama).

* **Fungsi Halaman:** Halaman kerja utama tempat pengguna melihat kartu nama bayi satu per satu, membaca arti singkat serta kecocokan fonetiknya, dan memutuskan untuk menyukai (*Like/Swipe*) atau melewati (*Skip*) nama tersebut.

#### A. Tema, Warna & Gaya Spesifik Halaman 1

* Latar belakang krem terang (`#F4F3EC`) yang bersih dan lapang, memberikan kontras tajam terhadap kartu *header* hijau gelap di bagian atas dan tombol aksi hijau daun di bagian tengah/bawah.

* Mengadopsi konsep **Tactile Card Stack** (tumpukan kartu fisik) di mana kartu di lapisan atas memiliki bayangan lembut (*soft drop shadow*) yang jatuh ke kartu di bawahnya.

#### B. Isi & Komponen UI Secara Detail (Dari Atas ke Bawah)

1. **Status Bar Sistem:** Jam `9:41` di kiri, *Dynamic Island* hitam di tengah, serta ikon sinyal, Wi-Fi, dan baterai di kanan.

2. **Kartu Header Status Pasangan (Dark Forest Green `#23331D`):**

   * **Kiri Atas:** Ikon avatar ganda yang bertumpuk (`Y` dan `A`) diikuti teks judul putih tebal **`You & Ava · one deck`**.

   * **Kiri Bawah (Statistik Real-time):** Teks kecil **`You liked 19 · Ava liked 12 · 2 matched`** (angka `19` dan `2` disorot warna hijau terang; setelah terjadi *match* pada detik `00:04`, teks ini berubah menjadi **`3 matched`**).

   * **Kanan:** Tombol lingkaran hijau medium dengan ikon *refresh/sync* panah melingkar, dilengkapi lencana (*badge*) bulat hijau terang bertuliskan angka **`12`** di pojok kanan atasnya.

3. **Bar Filter Deck (Kontainer Abu-Krem `#EAE9E0`):**

   * Di sisi kiri terdapat *pill badge* putih bertuliskan **`• Girl · Classic · 2 origins`** (dengan titik hijau di depan).

   * Di sebelahnya terdapat indikator jumlah kartu: **`— 41 of 214`**.

4. **Area Kartu Nama Utama (Tumpukan Kartu / Card Stack):**

   * **Kartu Lapis Pertama — "Aurora" (Terlihat sedang di-swipe pada `00:00` & `00:15`):**

     * **Baris Atas:** Label kategori **`GIRL · #24 IN 2025`** (teks `#24` berwarna oranye-terakota `#C85A32`), dan dua *tag pill* di kanan atas: **`Classic`** dan **`Latin`**.

     * **Judul Utama:** Teks raksasa **`Aurora`**.

     * **Deskripsi Arti:** **`dawn — Latin, first light`**.

     * **Indikator Pasangan:** Avatar kecil **`A`** dengan teks **`Ava liked this · 2h ago`** (memberi tahu bahwa pasangan sudah menyukai nama ini 2 jam lalu).

     * **Tombol Dalam Kartu:** *Pill button* lebar penuh berwarna hijau daun (`#6CC24A`) bertuliskan **`Swipe together`** $\rightarrow$.

   * **Kartu Lapis Kedua — "Wren" (Menjadi kartu aktif pada `00:01` dan `00:06 – 00:07`):**

     * **Baris Atas:** Label **`GIRL · #71 IN 2025`** (`#71` berwarna terakota), serta dua *tag pill* bertumpuk vertikal di kanan atas: **`Nature`** dan **`English`**.

     * **Judul Utama:** Teks raksasa **`Wren`**.

     * **Deskripsi Arti & Fonetik:** Baris pertama **`little wren — English, songbird`**, baris kedua **`REN HAYL · soft with Hale`**.

     * **Tombol Dalam Kartu:** *Pill button* lebar penuh berwarna hijau daun (`#6CC24A`) bertuliskan **`Swipe together`** $\rightarrow$.

5. **Baris Kontrol Aksi Cepat (Di Bawah Kartu):**

   * **Tombol Kiri:** Lingkaran putih berbayang lembut dengan ikon silang **`$\times$`** (untuk menolak/melewati nama).

   * **Tombol Tengah:** Lingkaran putih dengan ikon panah putar balik **`$\circlearrowleft$`** (*Undo* kartu sebelumnya).

   * **Tombol Kanan:** *Pill button* hijau daun (`#6CC24A`) dengan ikon hati hitam dan teks **`Like`**.

   * **Teks Penghitung Kartu:** Di bawah ketiga tombol terdapat teks kecil terpusat **`Card 7 of 41`** (berubah menjadi **`Card 8 of 41`** pada `00:01` setelah kartu Aurora di-*swipe*).

6. **Bottom Navigation Dock (Navigasi Bawah Melayang):**

   * Berbentuk kapsul besar berwarna putih di bagian paling bawah layar.

   * **Tab Kiri (Aktif):** Kapsul hijau daun (`#6CC24A`) dengan ikon tumpukan kartu dan teks tebal **`Deck`**.

   * **Tab Kanan (Tidak Aktif):** Ikon hati dengan teks **`Shortlist`** disertai lencana bulat hitam bertuliskan **`2`** (berubah menjadi **`3`** setelah *match*).

#### C. Efek Visual & Detail Animasi Per Detik pada Halaman 1

* **`00:00 – 00:01` (Animasi Swipe & Morphing Tombol Like):**

  * Pada `00:00`, kartu **"Aurora"** berada dalam posisi miring sekitar $-12^\circ$ di kanan atas layar.

  * Memasuki `00:01`, kartu **"Aurora"** tidak sekadar terlempar keluar layar, melainkan **menyusut (*scale down* dari** $100\%$ **ke** $25\%$**) sambil meluncur turun secara melengkung** menuju tombol **`Like`** di kanan bawah kartu **"Wren"**.

  * Secara bersamaan pada `00:01`, tombol **`Like`** berubah wujud (*morphing*) menjadi *toggle switch* hijau dengan bola indikator putih yang bergeser ke kanan, menandakan sistem sedang memproses aksi suka. Teks penghitung di bawahnya langsung berganti dari `Card 7 of 41` menjadi `Card 8 of 41`.

* **`00:06 – 00:07` (Keadaan Stabil & Transisi Pindah Tab):**

  * Setelah *overlay Match* ditutup pada `00:06`, halaman **Deck** tampil bersih menampilkan kartu **"Wren"** secara utuh. Tombol *toggle* kembali ke bentuk normal **`$\heartsuit$ Like`**.

  * Pada `00:07`, muncul indikator sentuhan (*tap ripple* bulat putih transparan) tepat di atas tab **`Shortlist 3`** pada navigasi bawah. Kapsul hijau mulai bergeser dari tab `Deck` ke tab `Shortlist 3`.

* **`00:14 – 00:15` (Animasi Kembali ke Deck & Reset Loop):**

  * Pada `00:14`, saat pengguna kembali dari halaman *Shortlist*, kartu dari halaman sebelumnya meluncur turun dan memudar, memperlihatkan kembali kartu **"Wren"** di halaman Deck, diikuti munculnya kembali kartu **"Aurora"** yang berayun ke atas pada `00:15` untuk menutup siklus *loop* video.

### HALAMAN 2: Layar Pop-Up / Overlay "It's a Match!" (Kecocokan Nama Pasangan)

* **Rentang Waktu Kemunculan:** `00:01` (akhir) hingga `00:06` (awal).

* **Fungsi Halaman:** Memberikan umpan balik emosional dan perayaan instan (*gamified reward*) ketika nama yang baru saja disukai pengguna ternyata juga telah disukai oleh pasangannya, sekaligus menggabungkan suara keduanya menjadi satu kartu nama lengkap dalam *Shortlist*.

#### A. Tema, Warna & Gaya Spesifik Halaman 2

* **Tema:** *Dramatic Spotlight & Celebration Overlay*.

* **Gaya & Warna:** Menggunakan efek **Glassmorphism Gelap** (`backdrop-filter: blur(8px)` dengan lapisan warna hijau-hitam transparan `rgba(22, 30, 18, 0.78)`) yang menutup Halaman 1 di belakangnya. Kontras dibangun melalui teks putih terang, kartu hijau hutan pekat (`#23331D`), dan cincin cahaya aksen oranye-terakota (`#C85A32`).

#### B. Isi & Komponen UI Secara Detail

1. **Header Selebrasi (Bagian Atas Tengah Overlay):**

   * Teks kecil huruf kapital renggang (*letter-spacing* lebar): **`BOTH LIKED`** berwarna krem kehijauan, dengan garis pembatas tipis horizontal di bawahnya.

   * Judul selebrasi: **`It's a match`** dalam warna putih tebal.

   * **Efek Cincin Halo:** Sebuah elips/cincin tipis berwarna oranye-terakota (`#C85A32`) dengan efek pendaran cahaya (*glow*) halus yang melingkari teks *"It's a match"*.

2. **Kartu Transisi Kiri & Kanan (Hanya muncul pada detik `00:02`):**

   * Dua kartu putih krem yang masuk dari tepi kiri (**`Liked by Ava — Aurora`**) dan tepi kanan (**`Liked by You — Aurora`**), dipertemukan oleh sebuah tombol bulat hijau di tengah yang memuat ikon panah penyatuan vertikal (`$\updownarrow$`).

3. **Kartu Hasil Penggabungan / Merged Match Card (Muncul pada detik `00:03 – 00:05`):**

   * Berupa kartu persegi panjang horizontal berwarna hijau hutan gelap (`#23331D`) dengan garis tepi (*stroke*) tipis berwarna hijau zaitun muda.

   * **Pojok Kiri Atas:** Avatar kembar **`Y`** dan **`A`** yang bertumpuk.

   * **Pojok Kanan Atas:** Lencana kapsul krem (`#EAE9E0`) bertuliskan **`3rd match`**.

   * **Tengah Kartu:** Nama lengkap gabungan **`Aurora Hale`** dalam ukuran besar berwarna putih, diikuti detail di bawahnya: **`dawn · uh-ROR-uh HAYL · #24`** $\uparrow 6$ (angka `#24` dan `↑6` berwarna oranye-terakota).

   * **Bawah Kartu:** Teks status berhuruf kapital **`BOTH LIKED · IN YOUR SHORTLIST`** berwarna hijau-abu redup.

4. **Pasangan Tombol Aksi Modal (Di Bawah Kartu Match pada `00:04 – 00:05`):**

   * **Tombol Kiri (Primary CTA):** *Pill button* hijau daun solid (`#6CC24A`) dengan teks hitam tebal **`Open the name`**.

   * **Tombol Kanan (Secondary CTA):** *Pill button* gelap semi-transparan dengan garis tepi tipis dan teks putih **`Keep swiping`**.

#### C. Efek Visual & Detail Animasi Per Detik pada Halaman 2

* **`00:01 – 00:02` (Fade-in Backdrop & Tabrakan Dua Kartu):**

  * Layar utama meredup secara halus dalam waktu sekitar $0.25\text{ detik}$. Teks **`BOTH LIKED`** muncul di tengah atas.

  * Dari luar batas layar kiri dan kanan, dua kartu putih meluncur masuk secara horizontal saling mendekat ke arah tengah layar. Di titik temu tengah, muncul ikon bulat hijau dengan panah penyatuan.

* **`00:02 – 00:03` (Animasi Peleburan / Card Merge & Pop-in):**

  * Kedua kartu putih tersebut menyatu di tengah layar dan bertransformasi (*morph & scale bounce*) menjadi satu kartu gelap **`Aurora Hale`**.

  * Saat kartu **`Aurora Hale`** sedikit membesar dan memantul halus (*spring physics*), judul **`It's a match`** beserta cincin cahaya terakota memudar masuk (*fade in*) tepat di atasnya.

* **`00:03 – 00:04` (Kemunculan Tombol Aksi & Sinkronisasi Latar Belakang):**

  * Kartu **`Aurora Hale`** bergeser turun sedikit ke posisi stabilnya, diikuti munculnya dua tombol **`Open the name`** dan **`Keep swiping`** dari bawah secara halus (*slide-up + fade-in*).

  * Perhatikan detail mikro di latar belakang yang redup: angka pada navigasi bawah otomatis bertambah dari **`Shortlist 2`** menjadi **`Shortlist 3`**, dan teks pada kartu header atas berubah menjadi **`3 matched`**.

* **`00:05 – 00:06` (Interaksi Klik & Penutupan Overlay):**

  * Pada `00:05`, indikator sentuhan (*tap circle*) menekan tombol **`Keep swiping`** di sebelah kanan.

  * Seluruh lapisan *overlay* gelap beserta kartu **`Aurora Hale`** memudar keluar (*fade out*) pada `00:06`, mengembalikan tampilan ke Halaman 1 (Deck).

### HALAMAN 3: Layar "Shortlist" (Daftar Nama Favorit Pasangan)

* **Rentang Waktu Kemunculan:** `00:08 – 00:09` (Tampil penuh), serta menjadi layar latar belakang pada `00:10 – 00:13`.

* **Fungsi Halaman:** Menyimpan dan menampilkan seluruh daftar nama bayi yang telah disetujui bersama oleh kedua pasangan, diurutkan dari yang paling baru mendapatkan *match*, serta menyediakan akses ke daftar nama yang pernah dilewati (*Skipped*).

#### A. Tema, Warna & Gaya Spesifik Halaman 3

* **Tema:** *Curated Collection & Hierarchical Stack*.

* **Gaya & Warna:** Menggunakan gradasi warna kartu berdasarkan kebaruan waktu (*recency hierarchy*). Kartu yang baru saja ditambahkan memiliki warna hijau hutan paling gelap dan kontras (`#23331D`), kartu-kartu sebelumnya berwarna hijau zaitun medium (`#3A4633`), sedangkan kartu urutan bawah ditampilkan dalam bentuk baris ringkas berwarna hijau sage pudar (`#6E7865`).

#### B. Isi & Komponen UI Secara Detail (Dari Atas ke Bawah)

1. **Header Halaman Shortlist:**

   * **Kiri:** Judul besar **`Shortlist`** berwarna hitam pekat, didampingi lencana lingkaran hijau daun (`#6CC24A`) bertuliskan angka **`3`**.

   * **Kanan:** Tombol kapsul krem berbingkai halus dengan ikon putar balik: **`$\circlearrowleft$ Skipped · 23`** (menunjukkan ada 23 nama yang dilewati dan bisa ditinjau ulang).

   * **Sub-judul Header:** Teks **`Both liked · synced with Ava's phone`** berwarna abu-hijau lembut.

2. **Daftar Kartu Nama Favorit (4 Item Vertikal):**

   * **Item 1 — Kartu "Aurora Hale" (Warna Hijau Hutan Gelap `#23331D`):**

     * Kiri atas: Avatar ganda `Y` & `A` diikuti judul **`Aurora Hale`**.

     * Kanan atas: Lencana kapsul krem bertuliskan **`Added just now`**.

     * Bawah: **`dawn · uh-ROR-uh HAYL · #24 in 2025`** $\uparrow 6$ (`#24` dan `↑6` disorot warna terakota).

   * **Item 2 — Kartu "Soren Hale" (Warna Hijau Zaitun `#3A4633`):**

     * Kiri atas: Avatar ganda `Y` & `A` diikuti judul **`Soren Hale`**.

     * Kanan atas: Lencana kapsul krem bertuliskan **`Both liked Sep 21`**.

     * Bawah: **`stern · SOR-in HAYL · #188`**.

   * **Item 3 — Kartu "Wren Hale" (Warna Hijau Zaitun `#3A4633`):**

     * Kiri atas: Avatar ganda `Y` & `A` diikuti judul **`Wren Hale`**.

     * Kanan atas: Lencana kapsul krem bertuliskan **`Both liked Sep 19`**.

     * Bawah: **`little wren · REN HAYL · #71`**.

   * **Item 4 — Bar Ringkas "Juniper Hale" (Warna Sage Pudar `#6E7865`):**

     * Berbentuk pita horizontal ramping di bagian bawah daftar bertuliskan huruf kapital **`JUNIPER HALE · #89`**.

3. **Bottom Navigation Dock:**

   * Tab **`Deck`** di kiri kini tidak aktif (latar putih), sedangkan tab **`Shortlist 3`** di kanan menyala aktif dengan kapsul hijau daun (`#6CC24A`).

#### C. Efek Visual & Detail Animasi Per Detik pada Halaman 3

* **`00:07 – 00:08` (Transisi Masuk Halaman Shortlist):**

  * Saat tab **`Shortlist 3`** disentuh pada `00:07`, konten halaman *Deck* memudar dan bergeser turun sementara daftar kartu pada halaman **Shortlist** meluncur masuk secara bertingkat (*staggered vertical slide*) dalam waktu sekitar $0.3\text{ detik}$, hingga tampil penuh dan tajam pada `00:08`.

* **`00:09` (Interaksi Pemilihan Kartu Aurora Hale):**

  * Pada `00:09`, indikator sentuhan (*tap indicator* bulat putih semi-transparan) muncul di bagian kanan bawah kartu paling atas (**`Aurora Hale`**). Sentuhan ini memberikan efek tekan halus (*subtle scale-down*) sebelum memicu kemunculan halaman detail di detik berikutnya.

### HALAMAN 4: Layar Bottom Sheet "Detail Nama & Statistik" (Studi Kasus: Aurora)

* **Rentang Waktu Kemunculan:** `00:10 – 00:13`.

* **Fungsi Halaman:** Menampilkan informasi ensiklopedis dan analitis secara mendalam mengenai satu nama spesifik, meliputi sejarah/arti nama, cara pengucapan audio beserta skor kecocokan dengan nama belakang keluarga, serta grafik tren popularitas selama 6 tahun terakhir.

#### A. Tema, Warna & Gaya Spesifik Halaman 4

* **Tema:** *Editorial Data Sheet & Audio-Visual Phonetics*.

* **Gaya & Warna:** Berbentuk **Modal Bottom Sheet** krem terang (`#FAF9F5`) dengan sudut atas melengkung besar ($32\text{ px}$) yang menutup sekitar $88\%$ tinggi layar. Bagian atas halaman **Shortlist** (`Shortlist 3` dan `Skipped · 23`) masih terlihat mengintip di balik lapisan gelap di bagian paling atas layar untuk menjaga konteks posisi pengguna (*spatial awareness*).

#### B. Isi & Komponen UI Secara Detail (Dari Atas ke Bawah)

1. **Handle & Elemen Puncak Sheet:**

   * Garis indikator geser (*drag handle*) abu-abu kecil di puncak tengah.

   * Di bawahnya terdapat ornamen setengah lingkaran krem berbingkai hijau tipis (menyerupai lekukan kartu indeks fisik).

   * Tautan navigasi kembali: **`< Shortlist`** di sisi kiri.

2. **Header Nama:**

   * Teks raksasa **`Aurora`** di kiri, berdampingan dengan label **`GIRL · LATIN`** di kanannya.

3. **Blok 1 — MEANING (Arti & Asal-Usul):**

   * Label bagian berhuruf kapital kecil: **`MEANING`**.

   * Paragraf penjelasan: **`From Latin aurora — dawn, first light of day. Roman goddess of the dawn.`**

4. **Blok 2 — SAY IT WITH HALE (Fonetik, Audio & Uji Kecocokan Nama Keluarga):**

   * Label bagian berhuruf kapital kecil: **`SAY IT WITH HALE`**.

   * **Teks Fonetik Utama:** **`uh-ROR-uh HAYL`** dalam ukuran besar dan tebal (`22pt Bold`).

   * **Tombol Audio Interaktif (Di Kanan):** Tombol bulat besar berwarna hijau daun (`#6CC24A`) dengan ikon pengeras suara (*speaker*) di tengahnya.

   * **Skor Kecocokan Bunyi:** Deretan 5 bintang (**`★★★★★`** — 4 bintang pertama berwarna oranye-terakota `#C85A32`, 1 bintang terakhir berwarna abu-krem) diikuti kesimpulan uji bunyi: **`No R-R clash · passes the backyard test`** (menjelaskan bahwa tidak ada tabrakan huruf R ganda antara *Aurora* dan *Hale*, serta nyaman diteriakkan saat memanggil anak di halaman belakang).

5. **Blok 3 — POPULARITY (Kartu Visualisasi Data Tren `#EAE9E0`):**

   * Berada di dalam kontainer kartu berwarna krem-batu (`#EAE9E0`).

   * **Header Statistik Kiri:** Label **`POPULARITY`** di atas teks besar **`#24 in 2025 ·`** $\uparrow 6\text{ vs } 2023$ (teks `↑6 vs 2023` berwarna oranye-terakota `#C85A32`).

   * **Header Statistik Kanan:** Teks **`3,918 girls named Aurora in 2025`** dengan angka `2025` dicetak tebal.

   * **Diagram Batang (Bar Chart 6 Tahun):**

     * Menampilkan 6 batang vertikal untuk tahun **`20`**, **`21`**, **`22`**, **`23`**, **`24`**, dan **`25`**.

     * Tinggi batang dan kepekatan warna hijaunya meningkat secara progresif dari kiri ke kanan: tahun `20`–`22` berwarna hijau sage muda (`#93AE82`), tahun `23` berwarna hijau medium (`#6A8D56`), dan tahun `24`–`25` berwarna hijau daun tua (`#436B32`), memvisualisasikan lonjakan popularitas nama Aurora.

   * **Catatan Waktu:** Di bawah grafik terdapat teks kecil **`Both liked · Sep 23, 9:41 PM`**.

6. **Tombol Aksi Bawah & Bottom Navigation Dock (`00:13`):**

   * Pada detik `00:13`, di bagian paling bawah *sheet* (mengintip tepat di bawah kapsul navigasi), terlihat dua tombol aksi manajemen status: **`Keep on shortlist`** (hijau di kiri) dan **`Move to History`** (abu-abu gelap di kanan).

   * Kapsul navigasi bawah (`Deck` dan `Shortlist 3`) tetap melayang di atas *bottom sheet*.

#### C. Efek Visual & Detail Animasi Per Detik pada Halaman 4

* **`00:09 – 00:10` (Animasi Slide-Up Bottom Sheet):**

  * Tepat setelah kartu `Aurora Hale` diketuk pada `00:09`, lembar detail **Aurora** meluncur naik dari batas bawah layar (*slide up transition* dengan kurva *ease-out* yang cepat namun lembut), sementara latar belakang halaman *Shortlist* meredup di bagian atas.

* **`00:11 – 00:12` (Animasi Micro-interaction Pemutar Audio Fonetik):**

  * Pada `00:11`, sebuah indikator ketukan (*tap indicator*) menyentuh tombol bulat speaker hijau di sebelah teks **`uh-ROR-uh HAYL`**.

  * Dari `00:11` hingga `00:12`, tombol bulat hijau tersebut **berubah menjadi indikator pemutar melingkar (*radial audio progress ring*)**: ikon speaker di tengah menghilang dan digantikan oleh cincin putih tebal di sekeliling tepi lingkaran hijau yang bergerak memutar searah jarum jam, menandakan suara pengucapan *"uh-ROR-uh HAYL"* sedang diputar selama kurang lebih $1.2\text{ detik}$.

* **`00:13` (Selesai Audio & Klik Navigasi Kembali ke Deck):**

  * Pada `00:13`, pemutaran audio selesai dan tombol kembali menampilkan ikon speaker normal.

  * Secara bersamaan, bagian bawah *sheet* sedikit bergeser memperlihatkan tombol `Keep on shortlist` / `Move to History`, lalu indikator sentuhan menekan tab **`Deck`** di kiri bawah navigasi, memicu penutupan *Bottom Sheet* untuk kembali ke Halaman 1 pada `00:14`.

## BAGIAN 3: TABEL KRONOLOGI END-TO-END (DETIK DEMI DETIK `00:00` – `00:15`)

Untuk merangkum bagaimana keempat halaman di atas saling terhubung menjadi satu pengalaman pengguna (*user flow*) yang utuh dan berkesinambungan (*seamless loop*), berikut adalah peta waktu per detiknya:

| Detik | Halaman Aktif | Aksi Pengguna / Sistem | Detail Pergerakan Animasi | 
| ----- | ----- | ----- | ----- | 
| **`00:00`** | **Halaman 1: Deck** | Pengguna menarik (*swipe*) kartu **"Aurora"**. | Kartu **"Aurora"** miring $-12^\circ$ di kanan atas; kartu **"Wren"** sudah terlihat menunggu di lapis bawahnya. | 
| **`00:01`** | **Halaman 1** $\rightarrow$ **2** | Kartu **"Aurora"** dilepas ke status *Liked*. | Kartu **"Aurora"** mengecil dan tersedot masuk ke tombol **`Like`**. Tombol **`Like`** berubah menjadi *toggle switch* aktif. Penghitung kartu berganti ke `Card 8 of 41`. | 
| **`00:02`** | **Halaman 2: Match Overlay** | Sistem mendeteksi Ava juga menyukai **"Aurora"**. | Layar meredup (*dark blur*). Dua kartu putih (`Liked by Ava` & `Liked by You`) meluncur dari tepi kiri dan kanan menuju ikon penyatuan di tengah. | 
| **`00:03`** | **Halaman 2: Match Overlay** | Kedua kartu melebur menjadi satu kecocokan. | Dua kartu putih menyatu menjadi kartu hijau gelap **`Aurora Hale (3rd match)`**. Teks **`It's a match`** dengan cincin cahaya oranye-terakota menyala di atasnya. | 
| **`00:04`** | **Halaman 2: Match Overlay** | Opsi lanjutan ditampilkan kepada pengguna. | Tombol **`Open the name`** dan **`Keep swiping`** muncul di bawah kartu *match*. Badge navigasi bawah di latar belakang otomatis bertambah menjadi **`Shortlist 3`**. | 
| **`00:05`** | **Halaman 2: Match Overlay** | Pengguna mengetuk tombol **`Keep swiping`**. | Indikator *tap* putih muncul di atas tombol **`Keep swiping`**. | 
| **`00:06`** | **Halaman 1: Deck** | *Overlay Match* tertutup, kembali ke tumpukan kartu. | *Overlay* memudar hilang. Kartu **"Wren"** tampil jelas sebagai kartu utama berikutnya di halaman Deck. | 
| **`00:07`** | **Halaman 1** $\rightarrow$ **3** | Pengguna mengetuk tab **`Shortlist 3`** di bawah. | Indikator *tap* menekan tab **`Shortlist 3`**. Indikator kapsul hijau pada *bottom dock* bergeser dari kiri (`Deck`) ke kanan (`Shortlist 3`). | 
| **`00:08`** | **Halaman 3: Shortlist** | Halaman daftar favorit tampil penuh. | Empat kartu (`Aurora Hale`, `Soren Hale`, `Wren Hale`, `Juniper Hale`) tersusun rapi dengan lencana **`Added just now`** pada kartu teratas. | 
| **`00:09`** | **Halaman 3: Shortlist** | Pengguna mengetuk kartu **`Aurora Hale`**. | Indikator *tap* muncul pada kartu teratas **`Aurora Hale`** untuk membuka rincian nama. | 
| **`00:10`** | **Halaman 4: Detail Sheet** | *Bottom Sheet* detail **"Aurora"** terbuka. | Lembar putih-krem meluncur naik dari bawah menutupi $88\%$ layar, menampilkan arti nama, uji fonetik **`uh-ROR-uh HAYL`**, dan grafik popularitas 2020–2025. | 
| **`00:11`** | **Halaman 4: Detail Sheet** | Pengguna mengetuk tombol speaker audio. | Indikator *tap* menyentuh tombol bulat hijau di kanan teks **`uh-ROR-uh HAYL`**. | 
| **`00:12`** | **Halaman 4: Detail Sheet** | Audio pengucapan nama sedang diputar. | Ikon speaker berubah menjadi cincin *progress* putih yang berputar mengelilingi tepi lingkaran hijau. | 
| **`00:13`** | **Halaman 4** $\rightarrow$ **1** | Audio selesai; pengguna mengetuk tab **`Deck`**. | Tombol speaker kembali normal. Indikator *tap* menyentuh tab **`Deck`** di navigasi bawah, sementara tombol aksi bawah (`Keep on shortlist` & `Move to History`) terlihat sekilas. | 
| **`00:14`** | **Halaman 1: Deck** | *Bottom Sheet* tertutup, kembali ke layar **Deck**. | Lembar detail meluncur turun dan memudar, memperlihatkan kembali kartu **"Wren"** dan header **`You & Ava · one deck`**. | 
| **`00:15`** | **Halaman 1: Deck** | Siklus animasi kembali ke titik awal (*Loop*). | Kartu **"Aurora"** kembali melayang di atas kartu **"Wren"** persis seperti posisi pada `00:00` untuk menciptakan *seamless loop*. | 

---

## BAGIAN 4: ARSITEKTUR NAVIGASI, FILTER, PENCARIAN & ANALISIS SIDEBAR (NAVIGATION & INFORMATION ARCHITECTURE)

Bagian ini mengupas secara spesifik bagaimana pengguna berpindah antar-layar, bagaimana pencarian/penyaringan (*filtering*) dilakukan, serta keputusan arsitektur UI terkait ada atau tidaknya *Sidebar* maupun *Search Bar* konvensional.

### 1. Mengapa Tidak Ada Hamburger Sidebar & Search Bar Konvensional?
Secara desain, aplikasi ini **TIDAK menggunakan Hamburger Menu / Left-Right Sidebar Drawer** maupun kolom input pencarian teks (*text search input*) di layar utama. Keputusan ini disengaja berdasarkan prinsip **Thumb-Zone Ergonomics & Focused Decision Making**:
* **Pengganti Sidebar:** Seluruh struktur tingkat atas (*Top-Level Navigation*) dipadatkan menjadi **2 Tab Utama** pada **Floating Bottom Navigation Dock** (`Deck` dan `Shortlist`). Pengguna tidak perlu menjangkau pojok kiri atas layar untuk membuka *sidebar*.
* **Pengganti Search Bar Konvensional:** Karena tujuan aplikasi adalah **eksplorasi terkurasi bersama pasangan** (*"One Deck · Two Votes"*), pencarian bebas digantikan oleh **Contextual Filter Bar** (`• Girl · Classic · 2 origins — 41 of 214`). Alih-alih mengetik satu nama, pasangan mengatur parameter *deck* melalui pil filter tersebut agar keduanya mendapatkan tumpukan kartu (*deck*) yang identik.

### 2. Anatomi & Mekanisme "Floating Bottom Navigation Dock" (Navigasi Utama)
Komponen navigasi utama ini selalu melayang (*persistent floating dock*) di bagian tengah bawah layar pada **semua halaman** (bahkan tetap berada di lapisan paling atas / `z-index` tertinggi saat *Match Overlay* dan *Detail Bottom Sheet* terbuka).

* **Bentuk & Ukuran Kontainer Luar:**
  * Berbentuk kapsul penuh (*full pill* dengan `border-radius: 999px`), lebar sekitar $72\%$ dari lebar layar mobile ($\approx 260\text{ px}$), dan tinggi sekitar $56\text{ px}$.
  * Warna latar kontainer: Putih bersih (`#FFFFFF`) dengan *padding* dalam sebesar $4\text{ px}$ di seluruh sisi dan bayangan lembut (*ambient elevation shadow*: `0px 8px 24px rgba(24, 32, 21, 0.08)`).
* **Struktur Dua Tab di Dalam Dock:**
  1. **Tab Kiri (`Deck`):**
     * Memuat ikon dua kartu bertumpuk di sisi kiri (ukuran $18 \times 18\text{ px}$) dan label teks **`Deck`** (`14pt SemiBold`).
  2. **Tab Kanan (`Shortlist`):**
     * Memuat ikon hati (*outline* tipis saat tidak aktif, atau *solid/filled* saat aktif) diikuti teks **`Shortlist`** (`14pt SemiBold`) dan **Counter Badge** bulat (`18px` diameter) yang menampilkan jumlah *match* saat ini (`2` lalu berubah menjadi `3`).
* **Logika Warna & Animasi Perpindahan Tab:**
  * **Indikator Aktif (Sliding Active Pill):** Berupa kapsul berwarna hijau daun (`#6CC24A`) yang mengisi penuh salah satu sisi tab. Ketika pengguna mengetuk tab sebelahnya (seperti pada detik `00:07` dan `00:13`), kapsul hijau ini **meluncur secara horizontal** (*sliding pill indicator*) dari satu sisi ke sisi lainnya.
  * **Perubahan Warna Badge Angka pada Tab `Shortlist`:**
    * Saat tab `Shortlist` **Tidak Aktif** (Halaman 1 & 2): Lingkaran *badge* angka berwarna hitam-hijau pekat (`#182015`) dengan angka putih.
    * Saat tab `Shortlist` **Aktif** (Halaman 3 & 4): Karena latar tab berubah menjadi hijau daun (`#6CC24A`), lingkaran *badge* tetap hitam pekat namun teks angka di dalamnya berubah menjadi hijau terang agar serasi dengan tema.

### 3. Mekanisme Filter Bar / Deck Selector (Pengganti Search & Kategori)
Terletak tepat di bawah *Header Status Pasangan* pada Halaman 1 (`Deck`):
* **Kontainer Bar (`#EAE9E0`):** Berbentuk kapsul persegi panjang membulat (`border-radius: 18px`, tinggi $\approx 42\text{ px}$) yang membentang selebar area konten utama.
* **Interactive Filter Pill (Kiri):** Di dalam bar terdapat kapsul putih (`#FFFFFF`) yang berfungsi sebagai tombol pemicu filter (*Filter Trigger*). Di dalamnya terdapat:
  * **Indikator Status Aktif:** Titik bulat kecil (`6px`) berwarna hijau daun (`#6CC24A`).
  * **Ringkasan Parameter Aktif:** Teks **`Girl · Classic · 2 origins`** (`13pt Medium`, warna `#182015`). Ini merangkum 3 dimensi filter sekaligus: **Gender** (*Girl*), **Gaya/Gaya Bahasa** (*Classic*), dan **Asal Bahasa** (*2 origins*, misalnya *Latin* & *English*).
* **Progress Counter (Kanan):** Teks **`— 41 of 214`** (`13pt Regular`, warna `#6E7865`) yang memberi tahu pengguna bahwa filter tersebut menghasilkan total **214 nama**, dan pengguna saat ini berada pada posisi pemeriksaan ke-41 dalam populasi deck tersebut.
* **Representasi Eksternal di Luar Layar HP:** Penting dicatat bahwa di sisi kiri luar bingkai iPhone pada video, desainer menampilkan *floating badge* **`• Girl · Classic · 2 origins`** yang diperbesar sebagai penekanan visual bahwa sistem filter multi-parameter ini adalah salah satu fitur unggulan aplikasi.

### 4. Navigasi Sekunder, Riwayat & Pemulihan (Secondary & Recovery Navigation)
Selain navigasi bawah, aplikasi memiliki **5 jalur navigasi kontekstual**:
1. **Tombol Sinkronisasi Pasangan (`↻ 12`) di Kanan Atas Header Deck:** Berfungsi menyinkronkan paksa atau membuka daftar 12 aksi terbaru yang baru saja dilakukan oleh pasangan (Ava).
2. **Tombol Undo (`↺`) di Tengah Bawah Kartu Deck:** Mengembalikan kartu yang tidak sengaja ter-*swipe* ke kiri atau ke kanan.
3. **Tombol Riwayat Nama yang Dilewati (`↺ Skipped · 23`) di Kanan Atas Halaman Shortlist:** Berfungsi sebagai pintu masuk menuju halaman/laci *History/Trash*, tempat 23 nama yang sebelumnya ditolak disimpan sementara jika pasangan berubah pikiran.
4. **Breadcrumb Kembali (`< Shortlist`) di Puncak Bottom Sheet Detail:** Memungkinkan penutupan lembar detail untuk kembali satu tingkat ke daftar *Shortlist* (selain menggunakan gestur tarik ke bawah pada *drag handle*).
5. **Dual Action Bar di Kaki Bottom Sheet (`Keep on shortlist` & `Move to History`):** Terlihat pada detik `00:13`, berfungsi memindahkan nama dari daftar aktif ke arsip riwayat tanpa menghapus datanya secara permanen.

---

## BAGIAN 5: ANATOMI MENDALAM SETIAP JENIS CARD (COMPREHENSIVE CARD ANATOMY & SPECS)

Aplikasi ini dibangun sepenuhnya di atas arsitektur berbasis kartu (*Card-Based Architecture*). Terdapat **7 jenis kartu berbeda** di dalam video ini. Berikut adalah bedah anatomi, ukuran, *padding*, dan struktur internal untuk masing-masing kartu.

### 1. Card Tipe A: Couple Sync Status Card (Header Kartu Pasangan)
* **Lokasi:** Puncak Halaman 1 (`Deck`).
* **Dimensi & Geometri:** Lebar penuh mengikuti margin layar ($\approx 345\text{ px}$), tinggi $\approx 82\text{ px}$, `border-radius: 24px`, `padding: 16px 18px`.
* **Warna & Permukaan:** Latar belakang *Deep Forest Green* (`#23331D`), tanpa garis tepi, memberikan bobot visual berat di bagian atas sebagai "jangkar" layar.
* **Tata Letak Internal (2 Kolom Asimetris):**
  * **Kolom Kiri ($80\%$ Lebar):**
    * Baris 1: Widget Avatar Kembar (`Y` + `A`) + spasi $10\text{ px}$ + Teks **`You & Ava · one deck`** (`16pt SemiBold`, `#FFFFFF`).
    * Baris 2 (Margin atas $8\text{ px}$): Teks statistik **`You liked 19 · Ava liked 12 · 2 matched`** (`12.5pt Medium`). Kata *You liked*, *Ava liked*, dan *matched* berwarna abu-hijau redup (`#8F9E88`), sedangkan angka **`19`** dan **`2`** (atau **`3`**) diberi warna hijau terang (`#6CC24A`) dan angka **`12`** berwarna putih krem.
  * **Kolom Kanan ($20\%$ Lebar, Rata Kanan Tengah):**
    * Tombol bulat *Sync* (`40px` diameter) berwarna `#34472C` dengan lencana notifikasi hijau daun (`#6CC24A`) bertuliskan **`12`**.

### 2. Card Tipe B: Primary Swipe Deck Card (Kartu Eksplorasi Nama: "Aurora" & "Wren")
* **Lokasi:** Area tengah Halaman 1 (`Deck`), tersusun bertumpuk (*Z-stack*).
* **Dimensi & Geometri:** Lebar $\approx 345\text{ px}$, tinggi $\approx 310\text{ px}$, `border-radius: 28px`, `padding: 22px`.
* **Warna & Elevasi:** Latar belakang *Pure Card Cream* (`#FAF9F5`), garis tepi ultra-tipis `1px solid rgba(24, 32, 21, 0.05)`, dengan *drop shadow* berlapis (`0px 16px 36px rgba(24, 32, 21, 0.07)`) agar terpisah jelas dari kartu di bawahnya.
* **Struktur Grid Internal (4 Zona Vertikal):**
  1. **Zona 1 — Header Metadata Kartu:**
     * Kiri atas: Teks kapital kecil **`GIRL · #24 IN 2025`** (atau **`GIRL · #71 IN 2025`**) dengan `letter-spacing: 0.06em`, ukuran `11pt Bold`. Kata `GIRL ·` dan `IN 2025` berwarna `#6E7865`, sedangkan peringkat **`#24`** / **`#71`** berwarna *Terracotta* (`#C85A32`).
     * Kanan atas: Tumpukan vertikal 2 *Origin/Style Tag Pills* (`Classic` + `Latin` pada Aurora; `Nature` + `English` pada Wren). Masing-masing pil berukuran tinggi $24\text{ px}$, latar `#EAE9E0`, teks `11pt Medium #182015`, jarak antar-pil $4\text{ px}$.
  2. **Zona 2 — Focal Point (Nama Bayi):**
     * Margin atas $\approx 28\text{ px}$. Teks **`Aurora`** atau **`Wren`** menggunakan ukuran raksasa (`52pt Bold`, warna `#182015`, `line-height: 1.0`, `letter-spacing: -0.03em`).
  3. **Zona 3 — Arti, Fonetik & Status Pasangan:**
     * Baris 1 (Arti singkat): Teks **`dawn — Latin, first light`** atau **`little wren — English, songbird`** (`14pt Regular`, warna `#5A6354`).
     * Baris 2 (Kondisional — Informasi Pasangan atau Uji Fonetik):
       * Jika pasangan sudah menyukai kartu tersebut (kasus **Aurora**): Muncul indikator avatar hijau tua **`A`** (`18px`) diikuti teks **`Ava liked this · 2h ago`** (`13pt Medium #5A6354`).
       * Jika belum ada aksi pasangan (kasus **Wren**): Muncul panduan fonetik langsung **`REN HAYL · soft with Hale`** (`13pt Medium #7A8274`).
  4. **Zona 4 — In-Card Primary CTA Button:**
     * Di bagian paling bawah kartu terdapat tombol kapsul penuh (`width: 100%`, tinggi $48\text{ px}$, `border-radius: 999px`) berwarna `#6CC24A` dengan teks **`Swipe together`** $\rightarrow$ (`15pt SemiBold #182015`).

### 3. Card Tipe C: Dual Incoming Vote Cards (Kartu Transisi Kiri & Kanan Saat Match)
* **Lokasi:** Muncul eksklusif pada detik `00:02` di Halaman 2 (*Match Overlay*).
* **Fungsi Visual:** Memvisualisasikan secara harfiah konsep *"Two Votes"*—satu kartu datang dari ponsel Ava di sebelah kiri, dan satu kartu datang dari ponsel Anda di sebelah kanan.
* **Anatomi:**
  * Berwarna krem terang (`#FAF9F5`), `border-radius: 24px`, tinggi $\approx 150\text{ px}$.
  * **Kartu Kiri:** Menampilkan label kecil **`Liked by Ava`** di atas judul besar **`Aurora`**.
  * **Kartu Kanan:** Menampilkan label kecil **`Liked by You`** di atas judul besar **`Aurora`**.
  * Kedua kartu ini hanya bertahan selama $\approx 0.4\text{ detik}$ sebelum bertabrakan di titik tengah dan melebur menjadi Card Tipe D.

### 4. Card Tipe D: Merged Celebration Match Card (Kartu Hasil Kecocokan "Aurora Hale")
* **Lokasi:** Tengah layar pada Halaman 2 (`00:03 – 00:05`).
* **Dimensi & Geometri:** Lebar $\approx 315\text{ px}$, tinggi $\approx 168\text{ px}$, `border-radius: 26px`, `padding: 20px`.
* **Warna & Border:** Latar belakang *Deep Forest Green* (`#23331D`) dengan *inner border* halus `1.5px solid rgba(255, 255, 255, 0.14)` dan bayangan pekat `0px 24px 48px rgba(0, 0, 0, 0.45)`.
* **Struktur Internal:**
  * **Baris Atas:** Avatar ganda `Y` & `A` di kiri; kapsul krem `#EAE9E0` bertuliskan **`3rd match`** (`11.5pt SemiBold #182015`) di kanan.
  * **Baris Tengah:** Judul gabungan **`Aurora Hale`** (`26pt Bold #FFFFFF`), diikuti sub-baris **`dawn · uh-ROR-uh HAYL · #24 ↑6`** (`13pt Regular #B2BFA9`, dengan `#24 ↑6` berwarna `#C85A32`).
  * **Baris Bawah:** Teks kapital renggang **`BOTH LIKED · IN YOUR SHORTLIST`** (`10.5pt Bold #7B8B73`, `letter-spacing: 0.08em`).

### 5. Card Tipe E: Shortlist Tiered Cards (3 Tingkat Hierarki Kartu di Halaman Shortlist)
Pada Halaman 3 (`Shortlist`), desain kartu menggunakan **Sistem Gradasi 3 Tingkat (3-Tier Visual Aging)** untuk membedakan nama yang baru saja cocok dengan nama yang sudah disimpan beberapa hari sebelumnya:

| Tingkat (Tier) | Contoh Kartu di Video | Warna Latar Kartu | Tinggi Kartu | Ciri Khas & Lencana Waktu (Badge) |
| :--- | :--- | :--- | :--- | :--- |
| **Tier 1: Baru Saja Match (Newest)** | **`Aurora Hale`** | `#23331D` (*Deep Forest Green*) | $\approx 96\text{ px}$ | Kontras tertinggi. Lencana kanan atas: **`Added just now`**. Menampilkan tren lengkap `#24 in 2025 ↑6`. |
| **Tier 2: Match Sebelumnya (Recent)** | **`Soren Hale`** & **`Wren Hale`** | `#3A4633` (*Muted Olive Green*) | $\approx 92\text{ px}$ | Warna sedikit lebih terang/lembut. Lencana kanan atas menampilkan tanggal: **`Both liked Sep 21`** & **`Both liked Sep 19`**. |
| **Tier 3: Terlipat / Lama (Collapsed)** | **`JUNIPER HALE · #89`** | `#6E7865` (*Sage Bark*) | $\approx 36\text{ px}$ | Berbentuk bar tipis tanpa avatar dan tanpa sub-baris fonetik, menampilkan teks kapital penuh untuk menghemat ruang vertikal. |

### 6. Card Tipe F: Popularity Analytics Bento Card (Kartu Grafik di Dalam Bottom Sheet)
* **Lokasi:** Bagian bawah Halaman 4 (*Detail Bottom Sheet*).
* **Dimensi & Warna:** Lebar penuh di dalam *sheet*, tinggi $\approx 195\text{ px}$, `border-radius: 22px`, `padding: 16px`, warna latar `#EAE9E0` (*Soft Sand*).
* **Isi:** Menggabungkan informasi tekstual di bagian atas (label `POPULARITY`, peringkat `#24 in 2025 · ↑6 vs 2023`, dan angka kelahiran `3,918 girls named Aurora in 2025`) dengan widget diagram batang 6 kolom di bagian tengah, serta *timestamp* sinkronisasi `Both liked · Sep 23, 9:41 PM` di bagian paling bawah.

### 7. Card Tipe G: External Presentation Floating Card (Kartu Widget di Kanan Bawah Luar HP)
* **Lokasi:** Melayang di kanvas presentasi sebelah kanan bawah di luar bodi iPhone sepanjang video (`00:00 – 00:15`).
* **Anatomi & Perbedaan dengan Kartu di Dalam Aplikasi:**
  * Berwarna latar krem terang (`#FAF9F5`) dengan sudut membulat `20px` dan bayangan lembut di atas latar belakang lumut gelap.
  * Baris 1: Avatar ganda `Y` & `A` di sebelah kiri judul tebal **`Aurora Hale`** (`15pt Bold #182015`).
  * Baris 2: Teks **`dawn · uh-ROR-uh HAYL · #24`** diikuti baris **`↑6`** berwarna oranye-terakota (`#C85A32`).
  * Baris 3: Sebuah *pill badge* berbingkai hijau tipis dengan latar hijau sangat muda (`#EBF5E6`) dan teks hijau tua **`New match`**. Kartu ini berfungsi sebagai contoh tampilan **iOS Home Screen Widget / Notification Card** dari aplikasi tersebut.

---

## BAGIAN 6: BEDAH WIDGET, KOMPONEN MIKRO & ELEMEN INTERAKTIF (WIDGETS & MICRO-COMPONENTS)

Selain kartu berukuran besar, kekuatan utama desain ini terletak pada **Widget Mikro** yang dirancang sangat presisi.

### 1. Widget Avatar Kolaborasi Pasangan (`Y` & `A` Overlapping Avatars)
* **Struktur Visual:** Dua lingkaran sempurna yang saling bertumpuk secara horizontal (lingkaran kanan menimpa sekitar $28\%$ sisi kanan lingkaran kiri).
* **Lingkaran Kiri (`Y` - You):** Latar hijau daun cerah (`#6CC24A`), teks huruf **`Y`** berwarna hitam pekat (`#182015`).
* **Lingkaran Kanan (`A` - Ava):** Latar hijau hutan medium (`#2E5A27`), teks huruf **`A`** berwarna putih/krem terang, dilengkapi *cutout border* (`2px solid`) yang warnanya selalu menyesuaikan warna latar kartu tempat ia berada (`#23331D` pada kartu gelap, atau `#FAF9F5` pada kartu terang).
* **Variasi Ukuran:**
  * Ukuran Standar ($28\text{ px}$): Digunakan pada Header Deck, Match Card, dan Shortlist Cards.
  * Ukuran Mikro Tunggal ($18\text{ px}$): Digunakan pada indikator `Ava liked this · 2h ago` di dalam kartu Aurora (hanya menampilkan avatar `A`).

### 2. Widget Tombol Sinkronisasi & Antrean (`↻ 12`)
* Terletak di pojok kanan kartu Header Halaman 1.
* Berupa tombol lingkaran berukuran $42 \times 42\text{ px}$ dengan latar `#33442B`.
* Di tengahnya terdapat ikon panah melingkar searah jarum jam (*refresh/sync icon*) berwarna krem terang.
* Di sudut kanan atas lingkaran menempel *notification pill* hijau terang (`#6CC24A`) berukuran $22 \times 16\text{ px}$ dengan teks hitam tebal **`12`**, menginformasikan jumlah aktivitas sinkronisasi baru dari ponsel pasangan.

### 3. Widget Penguji Fonetik & Pemutar Suara Interaktif ("Say It With Hale" Audio Widget)
Terletak di tengah Halaman 4 (*Detail Bottom Sheet*), widget ini menggabungkan 3 elemen sekaligus:
1. **Teks Panduan Ejaan Suara (`uh-ROR-uh HAYL`):** Suku kata yang mendapat tekanan pengucapan ditulis dalam huruf kapital penuh (`ROR` dan `HAYL`), sedangkan suku kata lemah ditulis dalam huruf kecil (`uh`), dipisahkan oleh tanda hubung.
2. **Tombol Audio 2-Status (Speaker $\rightarrow$ Radial Progress Ring):**
   * **Status 1 (Idle - `00:10` & `00:13`):** Lingkaran hijau daun (`#6CC24A`) berdiameter $44\text{ px}$ dengan ikon speaker hitam kecil di titik pusatnya.
   * **Status 2 (Playing - `00:11` & `00:12`):** Saat diketuk, ikon speaker menghilang dan di tepi dalam lingkaran hijau muncul **cincin progres putih tebal (`stroke-width: 3.5px`)** di atas jalur cincin hijau tua redup. Cincin putih ini berputar $360^\circ$ mengikuti durasi audio pengucapan nama.
3. **Bar Bintang & Indikator "Backyard Test":**
   * Menampilkan 5 ikon bintang ukuran $12\text{ px}$ (`★★★★☆`) dengan 4 bintang pertama berwarna terakota (`#C85A32`) dan bintang ke-5 berwarna abu-krem (`#D5D4C8`), berdampingan dengan teks evaluasi fonetik **`No R-R clash · passes the backyard test`**.

### 4. Widget Diagram Batang Tren Popularitas 6 Tahun (`20`–`25` Bar Chart)
Terletak di dalam kartu *Popularity* pada Halaman 4:
* **Struktur Kolom:** Terdiri dari 6 kolom vertikal dengan lebar masing-masing sekitar $38\text{ px}$ dan jarak antar-kolom (*gap*) sebesar $8\text{ px}$.
* **Bentuk Batang:** Setiap batang memiliki sudut atas membulat halus (`border-radius: 6px 6px 2px 2px`).
* **Logika Visual Tinggi & Warna Batang:**
  * Tahun **`20`**: Tinggi $\approx 28\%$, warna `#93AE82` (Sage Muda).
  * Tahun **`21`**: Tinggi $\approx 32\%$, warna `#93AE82` (Sage Muda).
  * Tahun **`22`**: Tinggi $\approx 35\%$, warna `#93AE82` (Sage Muda).
  * Tahun **`23`**: Tinggi $\approx 42\%$, warna `#6A8D56` (Hijau Medium — titik pembanding `vs 2023`).
  * Tahun **`24`**: Tinggi $\approx 68\%$, warna `#436B32` (Hijau Daun Tua).
  * Tahun **`25`**: Tinggi $\approx 85\%$, warna `#436B32` (Hijau Daun Tua — peringkat puncak `#24 in 2025`).
* **Garis Dasar (Baseline Axis):** Di bawah keenam batang terdapat garis horizontal tipis (`1px solid #C9C8BE`) yang memisahkan batang grafik dengan label angka tahun `20`, `21`, `22`, `23`, `24`, `25`.

### 5. Widget Kontrol Tindakan Cepat & Morphing Toggle (`×`, `↺`, `♥ Like`)
Terletak di bawah kartu utama pada Halaman 1:
* **Tombol Skip (`×`):** Lingkaran putih $48 \times 48\text{ px}$, ikon silang tipis `#182015`.
* **Tombol Undo (`↺`):** Lingkaran putih $48 \times 48\text{ px}$, ikon panah putar balik `#182015`.
* **Tombol `♥ Like` (Morphing Button):**
  * **Keadaan Normal (`00:00`, `00:06`, `00:14`):** Kapsul hijau daun (`#6CC24A`) berukuran lebar $\approx 96\text{ px}$ dan tinggi $48\text{ px}$, memuat ikon hati hitam dan teks **`Like`**.
  * **Keadaan Morphing / Terkunci Saat Match (`00:01 – 00:04`):** Begitu kartu Aurora masuk ke dalamnya, tombol ini menyempit sedikit menjadi $\approx 84\text{ px}$ dan berubah menjadi **Toggle Switch Aktif**: di sisi kiri muncul ikon hati putih kecil, dan di sisi kanan muncul lingkaran *thumb* putih berdiameter $36\text{ px}$. Ini memberikan kesan mekanis yang sangat memuaskan seolah pengguna baru saja "mengunci" pilihan suaranya.

### 6. Widget Kanvas Presentasi Eksternal (Keycaps `[ A ]` & `[ M ]` serta Tagline)
Di luar layar ponsel (pada kanvas presentasi video), terdapat elemen pendukung yang memperkaya estetika keseluruhan:
* **Header Kanvas Atas:** Teks **`BABY NAME MATCH`** di pojok kiri atas dan **`ONE DECK · TWO VOTES`** di pojok kanan atas (`11pt Bold`, huruf kapital renggang berwarna `#7B8465`).
* **Footer Kanvas Bawah:** Teks **`// Purrweb`** di pojok kiri bawah dan **`MOBILE CONCEPT`** di pojok kanan bawah.
* **3D Mechanical Keycap Widgets (`[ A ]` & `[ M ]`):**
  * Terletak mengapit bagian bawah bodi iPhone (satu di kiri bawah bertuliskan **`A`**, satu di kanan bawah bertuliskan **`M`**).
  * Didesain menyerupai tuts *keyboard* mekanik fisik berwarna krem gading (`#E6E4D8`) dengan bevel 3 dimensi, bayangan bawah yang tegas, dan kemiringan perspektif halus (`A` miring sedikit ke kiri, `M` miring sedikit ke kanan). Huruf `A` dan `M` merepresentasikan inisial konsep (**A**va / **A**urora & **M**atch) sekaligus memberi kesan *tactile hardware* pada presentasi.

---

## BAGIAN 7: SPESIFIKASI GRID, SPACING, ELEVASI & FISIKA ANIMASI (UI/UX ENGINEERING SPECS)

Bagian ini disediakan sebagai panduan teknis apabila desain ini ingin dibangun ulang (*recreate*) di Figma, Flutter, React Native, atau SwiftUI.

### 1. Sistem Grid & Ukuran Layar (Viewport & Layout Grid)
* **Target Viewport Perangkat:** iPhone 15 Pro / 16 Pro ($393 \times 852\text{ pt}$).
* **Margin Horizontal Layar (Screen Padding):** $20\text{ px}$ di sisi kiri dan $20\text{ px}$ di sisi kanan, menghasilkan area konten bersih selebar $353\text{ px}$.
* **Jarak Vertikal Antar-Komponen Utama (Vertical Rhythm):**
  * Jarak dari Status Bar ke Kartu Header Pasangan: $12\text{ px}$.
  * Jarak dari Kartu Header ke Bar Filter Deck: $10\text{ px}$.
  * Jarak dari Bar Filter ke Tumpukan Kartu Utama (*Swipe Card*): $16\text{ px}$.
  * Jarak dari Kartu Utama ke Baris Tombol Kontrol (`×`, `↺`, `Like`): $20\text{ px}$.
  * Jarak dari Teks `Card 7 of 41` ke Floating Bottom Navigation Dock: $18\text{ px}$.
  * Jarak dari Bottom Navigation Dock ke tepi bawah layar (*Home Indicator area*): $24\text{ px}$.

### 2. Spesifikasi Sudut Membulat (Border-Radius Tokens)
* `radius-xs` ($8\text{ px}$): Sudut atas batang grafik popularitas.
* `radius-sm` ($12\text{ px}$): *Tag pills* kecil (`Classic`, `Latin`, `Nature`, `English`, `Added just now`).
* `radius-md` ($18\text{ px}$): Kontainer Bar Filter dan kartu *Shortlist* tingkat ke-3 (`Juniper Hale`).
* `radius-lg` ($24\text{ px}$): Kartu Header Pasangan, Kartu *Shortlist* utama, dan Kartu *Popularity*.
* `radius-xl` ($28\text{ px} - 32\text{ px}$): Kartu *Swipe Deck* utama (`Aurora`, `Wren`) dan sudut atas *Detail Bottom Sheet*.
* `radius-full` ($999\text{ px}$): Seluruh tombol CTA (`Swipe together`, `Open the name`, `Keep swiping`, `Like`), *Bottom Navigation Dock*, serta avatar lingkaran.

### 3. Spesifikasi Fisika Animasi (Motion & Spring Physics)
Seluruh transisi di dalam aplikasi menghindari gerakan linier yang kaku dan menggunakan kurva pegas (*Spring Physics*) dengan rincian berikut:
* **Gestur Tarik & Lempar Kartu (`00:00 – 00:01`):**
  * Menggunakan *curved path trajectory* (lintasan melengkung parabolik) dari titik tengah atas menuju koordinat tombol `Like` di kanan bawah, disertai penyusutan skala (`scale(1.0)` $\rightarrow$ `scale(0.2)`) dan opasitas (`opacity: 1` $\rightarrow$ `0`) dalam durasi $380\text{ ms}$ (`cubic-bezier(0.32, 0.72, 0, 1)`).
* **Tabrakan & Peleburan Kartu Match (`00:02 – 00:03`):**
  * Dua kartu masuk dari posisi `translateX(-120%)` dan `translateX(120%)` menuju `translateX(0)` dalam $280\text{ ms}$.
  * Saat menyatu menjadi kartu **`Aurora Hale`**, terjadi efek pantulan pegas (*spring overshoot*: `scale(0.85)` $\rightarrow$ `scale(1.04)` $\rightarrow$ `scale(1.0)`) dengan parameter `stiffness: 320`, `damping: 22`.
* **Perpindahan Tab & Kemunculan Bottom Sheet (`00:07 – 00:10`):**
  * Kapsul hijau pada *Bottom Navigation Dock* bergeser menggunakan kurva `cubic-bezier(0.22, 1, 0.36, 1)` berdurasi $300\text{ ms}$.
  * *Detail Bottom Sheet* meluncur naik dari `translateY(100%)` ke `translateY(12%)` dalam durasi $420\text{ ms}$ dengan peredaman lembut tanpa pantulan berlebih (*critically damped spring*) agar teks panjang di dalamnya langsung nyaman dibaca.