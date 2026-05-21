<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enrichit la table native Laravel users avec les champs utiles de l'ancien schéma Django.
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('last_login')->nullable()->after('remember_token')->comment('Date de derniere connexion');
            $table->boolean('is_superuser')->default(false)->after('last_login')->comment('Ancien indicateur admin global (legacy)');
            $table->boolean('is_staff')->default(false)->after('is_superuser')->comment('Ancien indicateur staff (legacy)');
            $table->boolean('is_active')->default(true)->after('is_staff')->comment('Compte utilisateur actif/inactif');
            $table->string('fonction_metier', 20)->nullable()->after('is_active')->comment('Fonction metier libelle (distinct des roles Shield/Spatie)');
            $table->foreignId('owner_id')->nullable()->after('fonction_metier')->constrained('users')->comment('Utilisateur parent/proprietaire');
            $table->index('owner_id');
            $table->index('fonction_metier');
        });

        // Catalogue des evenements (culte, conference, retraite, etc.).
        Schema::create('events_event', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant unique de l evenement');
            $table->string('name', 200)->comment('Nom de l evenement');
            $table->string('type', 20)->comment('Type d evenement: retraite, culte, conference...');
            $table->dateTime('start_at')->nullable()->comment('Date/heure de debut');
            $table->dateTime('end_at')->nullable()->comment('Date/heure de fin');
            $table->string('location', 255)->comment('Lieu principal de l evenement');
            $table->unsignedInteger('capacity')->nullable()->comment('Capacite maximale autorisee');
            $table->decimal('price_to_pay', 12, 2)->default(0)->comment('Montant a payer pour participer a cet evenement');
            $table->string('currency', 5)->default('USD')->comment('Monnaie de reference pour le paiement');
            $table->string('access_auth_mode', 20)->default('password')->comment('Connexion utilisateurs: password ou otp');
            $table->string('access_otp_channel', 10)->nullable()->comment('Si otp: sms ou email');
            $table->boolean('is_active')->default(true)->comment('Evenement ouvert (actif) ou ferme');
            $table->timestamps();
            $table->index('start_at', 'events_event_start_at_idx');
            $table->index(['type', 'is_active'], 'events_event_type_is_active_idx');
            $table->index('type');
        });

        // Les tables legacy d'invitation (invite_table, invite_invite) ont ete supprimees.

        // Ateliers de retraite (numero unique par owner).
        Schema::create('retreat_atelier', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant atelier');
            $table->foreignId('owner_id')->nullable()->constrained('users')->comment('Responsable proprietaire de latelier');
            $table->foreignId('responsable_user_id')->nullable()->constrained('users')->comment('Ouvrier principal charge de latelier');
            $table->unsignedInteger('numero')->comment('Numero datelier');
            $table->string('role_on_atelier', 30)->default('responsable')->comment('Role operationnel du responsable atelier');
            $table->unique(['numero', 'owner_id'], 'retreat_atelier_numero_owner_unique');
            $table->boolean('is_active')->default(true)->comment('Atelier ouvert aux affectations ou ferme');
            $table->timestamps();
            $table->index('owner_id');
            $table->index('responsable_user_id');
        });

        // Chambres de retraite avec capacite et contrainte de sexe.
        Schema::create('retreat_chambre', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant chambre');
            $table->string('nom', 1)->comment('Nom/code court de chambre');
            $table->unsignedInteger('capacite')->comment('Capacite maximale');
            $table->string('sexe', 10)->nullable()->comment('Sexe autorise (homme/femme/mixte)');
            $table->foreignId('owner_id')->nullable()->constrained('users')->comment('Responsable de la chambre');
            $table->foreignId('responsable_user_id')->nullable()->constrained('users')->comment('Ouvrier principal charge de la chambre');
            $table->string('role_on_chambre', 30)->default('responsable')->comment('Role operationnel du responsable chambre');
            $table->unique(['nom', 'sexe', 'owner_id'], 'retreat_chambre_nom_sexe_owner_unique');
            $table->boolean('is_active')->default(true)->comment('Chambre ouverte aux affectations ou fermee');
            $table->timestamps();
            $table->index('owner_id');
            $table->index('responsable_user_id');
        });

        // Notifications utilisateur (lu/non lu) dans le contexte retraite.
        Schema::create('retreat_notification', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant notification');
            $table->string('message', 256)->comment('Message notifie');
            $table->string('link', 200)->nullable()->comment('Lien cible');
            $table->boolean('is_read')->comment('Notification lue ou non');
            $table->foreignId('user_id')->nullable()->constrained('users')->comment('Destinataire');
            $table->boolean('is_active')->default(true)->comment('Notification active ou archivee');
            $table->timestamps();
            $table->index('user_id');
        });

        // Regles generales a respecter durant l'evenement (participants, ouvriers, intervenants).
        Schema::create('retreat_policies', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant de la politique/reglement');
            $table->foreignId('event_id')->nullable()->constrained('events_event')->comment('Evenement cible (null = regle globale)');
            $table->string('category', 30)->comment('Categorie: reglement, condition, sanction');
            $table->string('title', 150)->comment('Titre de la regle');
            $table->text('content')->comment('Contenu detaille a respecter');
            $table->string('target_audience', 30)->default('all')->comment('Public cible: all, participant, worker, speaker');
            $table->unsignedInteger('severity_level')->default(1)->comment('Niveau de severite (1-5)');
            $table->boolean('is_mandatory')->default(true)->comment('Lecture/acceptation obligatoire');
            $table->boolean('is_active')->default(true)->comment('Regle active ou archivee');
            $table->dateTime('effective_from')->nullable()->comment('Date debut application');
            $table->dateTime('effective_to')->nullable()->comment('Date fin application');
            $table->foreignId('created_by')->nullable()->constrained('users')->comment('Auteur de la politique');
            $table->timestamps();

            $table->index(['event_id', 'category'], 'retreat_policies_event_category_idx');
            $table->index(['target_audience', 'is_active'], 'retreat_policies_audience_active_idx');
        });

        // Participants a la retraite (identite, paiement, billet, affectations).
        Schema::create('retreat_participant', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant participant');
            $table->string('nom', 100)->comment('Nom participant');
            $table->string('prenom', 100)->comment('Prenom participant');
            $table->unsignedInteger('age')->comment('Age participant');
            $table->string('preuve_paiement', 100)->nullable()->comment('Justificatif paiement');
            $table->boolean('paiement_valide')->comment('Paiement valide oui/non');
            $table->foreignId('atelier_id')->nullable()->constrained('retreat_atelier')->comment('Atelier assigne');
            $table->foreignId('chambre_id')->nullable()->constrained('retreat_chambre')->comment('Chambre assignee');
            $table->foreignId('user_id')->nullable()->constrained('users')->comment('Utilisateur createur');
            $table->string('email', 254)->nullable()->comment('Email participant');
            $table->string('sexe', 10)->nullable()->comment('Sexe participant');
            $table->string('telephone', 20)->nullable()->comment('Telephone principal');
            $table->string('qr_code', 100)->nullable()->comment('QR code de billet/acces');
            $table->string('adresse', 255)->nullable()->comment('Adresse physique');
            $table->text('observation')->nullable()->comment('Observations generales');
            $table->string('telephone_urgence', 20)->nullable()->comment('Contact urgence');
            $table->dateTime('date_presence')->nullable()->comment('Premiere date de presence');
            $table->boolean('present')->comment('Presence globale');
            $table->foreignId('owner_id')->nullable()->constrained('users')->comment('Ouvrier/encadreur responsable');
            $table->boolean('billet_envoye')->comment('Billet deja envoye');
            $table->dateTime('date_billet_envoye')->nullable()->comment('Date envoi billet');
            $table->string('billet_pdf', 100)->nullable()->comment('Fichier PDF billet');
            $table->char('download_token', 32)->unique()->comment('Token unique de telechargement billet');
            $table->string('role_participant', 20)->comment('Role du participant dans la retraite');
            $table->string('photo', 100)->nullable()->comment('Photo profil participant');
            $table->boolean('is_verified')->comment('Profil verifie');
            $table->boolean('billet_envoye_email')->comment('Billet envoye par email');
            $table->boolean('billet_envoye_whatsapp')->comment('Billet envoye par WhatsApp');
            $table->boolean('is_active')->default(true)->comment('Fiche participant ouverte (inscription/edition) ou fermee');
            $table->timestamps();
            $table->unique(['nom', 'prenom'], 'retreat_participant_nom_prenom_unique');
            $table->index('atelier_id');
            $table->index('chambre_id');
            $table->index('user_id');
            $table->index('owner_id');
        });

        // Trace l'acceptation des reglements/conditions/sanctions par chaque acteur.
        Schema::create('retreat_policy_acknowledgements', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant accusé de lecture');
            $table->foreignId('policy_id')->constrained('retreat_policies')->comment('Politique concernee');
            $table->foreignId('user_id')->nullable()->constrained('users')->comment('Ouvrier/intervenant ayant accepte');
            $table->foreignId('participant_id')->nullable()->constrained('retreat_participant')->comment('Participant ayant accepte');
            $table->boolean('has_read')->default(false)->comment('Lecture confirmee');
            $table->boolean('has_accepted')->default(false)->comment('Acceptation confirmee');
            $table->dateTime('acknowledged_at')->nullable()->comment('Date de validation');
            $table->string('signature_type', 20)->default('checkbox')->comment('Mode de validation: checkbox, otp, signature');
            $table->string('ip_address', 45)->nullable()->comment('IP lors de l acceptation');
            $table->boolean('is_active')->default(true)->comment('Accuse de lecture actif ou annule');
            $table->timestamps();

            $table->index('policy_id');
            $table->index('user_id');
            $table->index('participant_id');
        });

        // Details uniques d'une retraite (theme, speaker, notes) par evenement.
        Schema::create('retreat_retreatdetail', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant detail retraite');
            $table->string('theme', 200)->comment('Theme principal');
            $table->string('speaker', 200)->comment('Intervenant principal');
            $table->text('notes')->comment('Notes generales');
            $table->foreignId('event_id')->nullable()->unique()->constrained('events_event')->comment('Evenement retraite unique');
            $table->boolean('is_active')->default(true)->comment('Bloc detail retraite actif ou archive');
            $table->timestamps();
        });

        // Sessions/planning d'un evenement retraite.
        Schema::create('retreat_session', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant session');
            $table->string('title', 200)->comment('Titre session');
            $table->dateTime('start_at')->comment('Debut session');
            $table->dateTime('end_at')->comment('Fin session');
            $table->string('room', 100)->comment('Salle/zone session');
            $table->foreignId('event_id')->nullable()->constrained('events_event')->comment('Evenement parent');
            $table->boolean('is_active')->default(true)->comment('Session ouverte au planning ou annulee');
            $table->timestamps();
            $table->index(['event_id', 'start_at'], 'retreat_session_event_start_idx');
            $table->index('event_id');
        });

        // Temoignages soumis par les participants (avec moderation).
        Schema::create('retreat_testimony', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Identifiant temoignage');
            $table->string('name', 64)->comment('Nom affiche auteur');
            $table->text('message')->comment('Contenu temoignage');
            $table->string('color', 12)->comment('Couleur d affichage UI');
            $table->dateTime('date_submitted')->comment('Date soumission');
            $table->boolean('validated')->comment('Temoignage valide/modere');
            $table->boolean('is_active')->default(true)->comment('Temoignage visible ou masque');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_testimony');
        Schema::dropIfExists('retreat_session');
        Schema::dropIfExists('retreat_retreatdetail');
        Schema::dropIfExists('retreat_participant');
        Schema::dropIfExists('retreat_notification');
        Schema::dropIfExists('retreat_policy_acknowledgements');
        Schema::dropIfExists('retreat_policies');
        Schema::dropIfExists('retreat_chambre');
        Schema::dropIfExists('retreat_atelier');
        Schema::dropIfExists('events_event');

        // Rollback de l'enrichissement applique a la table users.
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['fonction_metier']);
            $table->dropColumn([
                'last_login',
                'is_superuser',
                'is_staff',
                'is_active',
                'fonction_metier',
                'owner_id',
            ]);
        });
    }
};
