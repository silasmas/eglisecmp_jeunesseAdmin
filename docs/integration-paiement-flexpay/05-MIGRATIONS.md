# Migrations base de données

## Table `dons` (pour projet dons)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dons', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->decimal('montant', 12, 2);
            $table->string('currency', 5)->default('USD');
            $table->string('nom')->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_reference')->nullable(); // orderNumber FlexPay
            $table->enum('etat', ['init', 'En cours', 'Payée', 'Annulée'])->default('init');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dons');
    }
};
```

## Modèle Don

Créer `app/Models/Don.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Don extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Pour un autre projet (ex: commandes)

Adapter les champs selon votre besoin :
- `commande_id` au lieu de `don_id`
- `total`, `channel`, `description`, etc.
