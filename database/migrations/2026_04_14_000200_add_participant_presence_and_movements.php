<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Etend le profil participant pour distinguer interne/externe et la politique de sortie.
        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->string('participant_type', 20)->default('internal')->after('role_participant')->comment('Type de participant: internal ou external');
            $table->boolean('exit_allowed')->default(false)->after('participant_type')->comment('Autorisation de sortie hors site de retraite');
            $table->time('curfew_time')->nullable()->after('exit_allowed')->comment('Heure limite de retour si sortie autorisee');
            $table->string('guardian_name', 150)->nullable()->after('curfew_time')->comment('Nom du parent/tuteur pour suivi securite');
            $table->string('guardian_phone', 20)->nullable()->after('guardian_name')->comment('Telephone du parent/tuteur');
            $table->string('registration_status', 20)->default('pending')->after('guardian_phone')->comment('Etat inscription: pending, otp_sent, otp_verified, completed');
            $table->string('registration_otp_code', 10)->nullable()->after('registration_status')->comment('Code OTP inscription en cours');
            $table->dateTime('registration_otp_sent_at')->nullable()->after('registration_otp_code')->comment('Date denvoi OTP');
            $table->dateTime('registration_otp_expires_at')->nullable()->after('registration_otp_sent_at')->comment('Date expiration OTP');
            $table->dateTime('registration_otp_verified_at')->nullable()->after('registration_otp_expires_at')->comment('Date verification OTP');
            $table->unsignedInteger('registration_otp_attempts')->default(0)->after('registration_otp_verified_at')->comment('Nombre de tentatives OTP');
            $table->index('participant_type');
            $table->index('exit_allowed');
            $table->index('registration_status');
        });

        // Paiement principal d'un participant pour un evenement selon le flux FlexPay.
        Schema::create('retreat_payments', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant unique du paiement');
            $table->foreignId('participant_id')->constrained('retreat_participant')->comment('Participant qui paie');
            $table->foreignId('event_id')->constrained('events_event')->comment('Evenement concerne (retraite)');
            $table->string('reference', 64)->unique()->comment('Reference interne unique (equivalent reference FlexPay)');
            $table->decimal('amount_expected', 12, 2)->comment('Montant attendu pour valider linscription');
            $table->decimal('amount_paid', 12, 2)->default(0)->comment('Montant effectivement paye');
            $table->string('currency', 5)->default('USD')->comment('Devise de paiement (USD, CDF, etc.)');
            $table->string('channel', 20)->comment('Canal: mobile_money ou card');
            $table->string('phone', 30)->nullable()->comment('Numero mobile pour paiement Mobile Money');
            $table->string('provider_reference', 100)->nullable()->comment('orderNumber retourne par FlexPay');
            $table->string('provider_status_code', 20)->nullable()->comment('Code brut retourne par FlexPay');
            $table->string('provider_message', 255)->nullable()->comment('Message brut retourne par FlexPay');
            $table->enum('etat', ['init', 'en_cours', 'payee', 'annulee', 'echouee', 'remboursee'])->default('init')->comment('Etat metier du paiement inspire de la logique FlexPay');
            $table->boolean('access_granted')->default(false)->comment('Vrai si paiement valide et acces evenement autorise');
            $table->dateTime('access_granted_at')->nullable()->comment('Date/heure de levee d acces');
            $table->foreignId('access_granted_by')->nullable()->constrained('users')->comment('Utilisateur qui a valide manuellement l acces');
            $table->dateTime('paid_at')->nullable()->comment('Date/heure de confirmation du paiement');
            $table->boolean('is_active')->default(true)->comment('Paiement actif (non annule logiquement) ou archive');
            $table->timestamps();

            $table->unique(['participant_id', 'event_id'], 'retreat_payments_participant_event_unique');
            $table->index(['event_id', 'etat'], 'retreat_payments_event_etat_idx');
            $table->index('provider_reference');
            $table->index('access_granted');
        });

        // Historique des transactions/updates de statut recus de FlexPay (polling, callback, redirection).
        Schema::create('retreat_payment_transactions', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant unique de la transaction');
            $table->foreignId('payment_id')->constrained('retreat_payments')->comment('Paiement parent');
            $table->string('transaction_type', 30)->comment('Type: initiation, callback, polling, verification');
            $table->string('provider_reference', 100)->nullable()->comment('orderNumber FlexPay');
            $table->string('provider_status_code', 20)->nullable()->comment('Code de statut fournisseur');
            $table->string('provider_status_label', 60)->nullable()->comment('Libelle du statut fournisseur');
            $table->json('request_payload')->nullable()->comment('Payload envoye a FlexPay');
            $table->json('response_payload')->nullable()->comment('Payload recu de FlexPay');
            $table->string('message', 255)->nullable()->comment('Message de contexte');
            $table->dateTime('processed_at')->comment('Date/heure de traitement transactionnel');
            $table->boolean('is_active')->default(true)->comment('Ligne transaction conservee ou neutralisee');
            $table->timestamps();

            $table->index('payment_id');
            $table->index('provider_reference');
            $table->index(['transaction_type', 'processed_at'], 'retreat_payment_tx_type_date_idx');
        });

        // Planifie chaque activite de l'evenement (liee a un jour et/ou une session).
        Schema::create('retreat_activity_plans', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant unique de l activite');
            $table->foreignId('event_id')->constrained('events_event')->comment('Evenement parent');
            $table->foreignId('session_id')->nullable()->constrained('retreat_session')->comment('Session planifiee eventuelle');
            $table->string('title', 200)->comment('Titre de l activite');
            $table->string('activity_type', 30)->comment('Type: enseignement, priere, atelier, service, etc.');
            $table->dateTime('starts_at')->comment('Debut prevu');
            $table->dateTime('ends_at')->comment('Fin prevue');
            $table->string('location', 150)->nullable()->comment('Lieu/salle de l activite');
            $table->boolean('is_mandatory')->default(false)->comment('Presence obligatoire ou non');
            $table->string('status', 20)->default('planned')->comment('Etat: planned, ongoing, done, cancelled');
            $table->text('notes')->nullable()->comment('Consignes ou details');
            $table->boolean('is_active')->default(true)->comment('Activite planifiee active ou annulee');
            $table->timestamps();

            $table->index('event_id');
            $table->index('session_id');
            $table->index(['event_id', 'starts_at'], 'retreat_activity_plans_event_starts_idx');
            $table->index(['activity_type', 'status'], 'retreat_activity_plans_type_status_idx');
        });

        // Enregistre la presence de chaque participant pour chaque activite.
        Schema::create('retreat_activity_attendances', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant de pointage');
            $table->foreignId('activity_plan_id')->constrained('retreat_activity_plans')->comment('Activite concernee');
            $table->foreignId('participant_id')->constrained('retreat_participant')->comment('Participant pointe');
            $table->string('status', 20)->default('absent')->comment('Etat: present, late, absent, excused');
            $table->dateTime('check_in_at')->nullable()->comment('Heure d entree/presence');
            $table->dateTime('check_out_at')->nullable()->comment('Heure de sortie');
            $table->string('scan_source', 20)->default('manual')->comment('Origine: manual, qr, nfc');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->comment('Agent ayant enregistre la presence');
            $table->text('note')->nullable()->comment('Justification ou remarque');
            $table->boolean('is_active')->default(true)->comment('Pointage actif ou corrige/annule');
            $table->timestamps();

            $table->unique(['activity_plan_id', 'participant_id'], 'retreat_activity_attendance_unique');
            $table->index('participant_id');
            $table->index('recorded_by');
            $table->index(['status', 'scan_source'], 'retreat_activity_attendance_status_scan_idx');
        });

        // Trace les sorties/retours des participants durant l'evenement.
        Schema::create('retreat_participant_movements', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant du mouvement');
            $table->foreignId('participant_id')->constrained('retreat_participant')->comment('Participant qui se deplace');
            $table->foreignId('event_id')->constrained('events_event')->comment('Evenement concerne');
            $table->string('movement_type', 20)->comment('Type: exit ou return');
            $table->dateTime('moved_at')->comment('Date/heure du mouvement');
            $table->foreignId('authorized_by')->nullable()->constrained('users')->comment('Utilisateur ayant autorise le mouvement');
            $table->string('reason', 150)->nullable()->comment('Motif de sortie/retour');
            $table->text('note')->nullable()->comment('Observation complementaire');
            $table->boolean('is_active')->default(true)->comment('Mouvement valide ou annule');
            $table->timestamps();

            $table->index('participant_id');
            $table->index('event_id');
            $table->index('authorized_by');
            $table->index(['movement_type', 'moved_at'], 'retreat_participant_movements_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_participant_movements');
        Schema::dropIfExists('retreat_activity_attendances');
        Schema::dropIfExists('retreat_activity_plans');
        Schema::dropIfExists('retreat_payment_transactions');
        Schema::dropIfExists('retreat_payments');

        Schema::table('retreat_participant', function (Blueprint $table) {
            $table->dropIndex(['participant_type']);
            $table->dropIndex(['exit_allowed']);
            $table->dropIndex(['registration_status']);
            $table->dropColumn([
                'participant_type',
                'exit_allowed',
                'curfew_time',
                'guardian_name',
                'guardian_phone',
                'registration_status',
                'registration_otp_code',
                'registration_otp_sent_at',
                'registration_otp_expires_at',
                'registration_otp_verified_at',
                'registration_otp_attempts',
            ]);
        });
    }
};
