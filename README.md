# Insurance CRM - Sigorta Acenteleri için Müşteri İlişkileri Yönetimi

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-green.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

## 📝 İçindekiler

- [Özellikler](#özellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Kullanım](#kullanım)
- [Güncelleme Geçmişi](#güncelleme-geçmişi)
- [Teknik Detaylar](#teknik-detaylar)
- [Katkıda Bulunma](#katkıda-bulunma)
- [Lisans](#lisans)

## ✨ Özellikler

### 👥 Müşteri Yönetimi
- Detaylı müşteri kayıtları
- TC Kimlik doğrulama
- Müşteri kategorileri (Bireysel/Kurumsal)
- Müşteri durumu takibi
- İletişim bilgileri yönetimi

### 📄 Poliçe Yönetimi
- Çoklu poliçe türü desteği
  - Trafik Sigortası
  - Kasko
  - Konut Sigortası
  - DASK
  - Sağlık Sigortası
  - Hayat Sigortası
- Poliçe dökümanları
- Otomatik yenileme hatırlatmaları
- Prim takibi
- Poliçe durumu izleme

### ✅ Görev Yönetimi
- Müşteri bazlı görevler
- Poliçe bazlı görevler
- Öncelik seviyeleri
- Görev hatırlatmaları
- Durum takibi

### 📊 Raporlama
- Poliçe yenileme oranları
- Müşteri dağılımları
- Prim üretim raporları
- Görev takip raporları

### ⚙️ Ayarlar ve Özelleştirme
- Şirket bilgileri
- E-posta bildirimleri
- SMTP entegrasyonu
- Otomatik hatırlatmalar
- Varsayılan değerler

## 🔧 Gereksinimler

- WordPress 6.0 veya üzeri
- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- WordPress REST API desteği
- modern_events_calendar eklentisi

## 📦 Kurulum

1. Eklenti dosyalarını `/wp-content/plugins/insurance-crm` dizinine yükleyin
2. WordPress yönetici panelinden eklentiyi etkinleştirin
3. "Insurance CRM > Ayarlar" menüsünden gerekli yapılandırmaları yapın

```bash
# Elle kurulum için
cd wp-content/plugins
git clone https://github.com/anadolubirlik/insurance-crm.git
cd insurance-crm
composer install
```

## 🚀 Kullanım

### Müşteri Ekleme

```php
// Programatik olarak müşteri ekleme
$customer = new Insurance_CRM_Customer();
$customer->add([
    'first_name' => 'Ahmet',
    'last_name' => 'Yılmaz',
    'tc_identity' => '12345678901',
    'email' => 'ahmet@example.com',
    'phone' => '05551234567'
]);
```

### Poliçe Ekleme

```php
// Programatik olarak poliçe ekleme
$policy = new Insurance_CRM_Policy();
$policy->add([
    'customer_id' => 1,
    'policy_number' => 'TRF-2025-001',
    'policy_type' => 'trafik',
    'start_date' => '2025-05-02',
    'end_date' => '2026-05-02',
    'premium_amount' => 1500.00
]);
```

## 📝 Güncelleme Geçmişi

### [1.0.0] - 2025-05-02
- İlk sürüm yayınlandı
- Temel özellikler eklendi
  - Müşteri yönetimi
  - Poliçe yönetimi
  - Görev yönetimi
  - Raporlama sistemi

## 🔧 Teknik Detaylar

### Veritabanı Şeması

```sql
-- Müşteriler tablosu
CREATE TABLE wp_insurance_crm_customers (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    tc_identity varchar(11) NOT NULL,
    email varchar(100) NOT NULL,
    phone varchar(20) NOT NULL,
    address text,
    category varchar(20) DEFAULT 'bireysel',
    status varchar(20) DEFAULT 'aktif',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Poliçeler tablosu
CREATE TABLE wp_insurance_crm_policies (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    customer_id bigint(20) NOT NULL,
    policy_number varchar(50) NOT NULL,
    policy_type varchar(50) NOT NULL,
    start_date date NOT NULL,
    end_date date NOT NULL,
    premium_amount decimal(10,2) NOT NULL,
    status varchar(20) DEFAULT 'aktif',
    document_path varchar(255),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Görevler tablosu
CREATE TABLE wp_insurance_crm_tasks (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    customer_id bigint(20) NOT NULL,
    policy_id bigint(20),
    task_description text NOT NULL,
    due_date datetime NOT NULL,
    priority varchar(20) DEFAULT 'medium',
    status varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

## 👥 Katkıda Bulunma

1. Bu depoyu fork edin
2. Yeni bir özellik dalı oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add some amazing feature'`)
4. Dalınıza push yapın (`git push origin feature/amazing-feature`)
5. Bir Pull Request oluşturun

## 📝 Lisans

Bu proje GNU General Public License v2.0 altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakınız.

---

Geliştirici: [Anadolu Birlik](https://github.com/anadolubirlik)  
Son Güncelleme: 2025-05-02