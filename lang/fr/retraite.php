<?php

return [
    'sms_confirmation_body' => 'CMP — Bonjour :name, paiement confirmé (réf. :ref). Votre billet avec QR code : :billet_url',

    'mail_registration_subject' => 'Confirmation d’inscription — :event',
    'mail_greeting' => 'Bonjour :name,',
    'mail_body_intro' => 'Votre inscription pour **:event** est confirmée. Ouvrez votre billet pour le QR code d\'accès, le règlement intérieur et la liste des histoires à apporter.',
    'mail_label_reference' => 'Référence paiement',
    'mail_label_amount' => 'Montant',
    'mail_label_room' => 'Chambre assignée',
    'mail_label_workshop' => 'Atelier assigné',
    'mail_placements_pending' => 'Vos affectations chambre et atelier seront visibles sur votre billet à partir du début officiel de la retraite.',
    'mail_billet_qr_hint' => 'Conservez ce billet : le QR code permettra de vérifier votre inscription à l\'accueil.',
    'mail_billet_documents_hint' => 'Sur votre page billet, vous pouvez consulter et télécharger le règlement intérieur et la liste des histoires à apporter à la retraite. Pensez à la consulter dès maintenant pour préparer votre venue.',
    'mail_workshop_number' => 'Atelier :n',
    'mail_button_portal' => 'Accéder au site',
    'mail_button_billet' => 'Voir mon billet',
    'mail_footer' => 'Merci de conserver cet e-mail. En cas de question, contactez le département de la Jeunesse.',

    'mail_admin_cash_subject' => 'Paiement cash à valider — :event',
    'mail_admin_cash_heading' => 'Nouvelle preuve de paiement en espèces',
    'mail_admin_cash_intro' => '**:name** vient de soumettre une preuve de paiement cash pour **:event**.',
    'mail_admin_cash_action' => 'Connectez-vous à l’espace d’administration pour valider ou rejeter ce paiement.',

    'sms_admin_cash_body' => 'CMP Admin — Paiement cash soumis par :name pour :event (réf. :ref). Validez dans l’admin.',

    'mail_payment_failure_subject' => 'Échec paiement inscription — :event (réf. :reference)',
    'mail_payment_failure_heading' => 'Échec de paiement d’inscription',
    'mail_payment_failure_intro' => 'Le paiement de **:name** pour **:event** n’a pas abouti.',
    'mail_payment_failure_action' => 'Consultez le tableau de bord d’administration (section Échecs paiement) pour le détail et le suivi.',

    'mail_atelier_report_subject' => 'Compte-rendu atelier :atelier — :activity',
    'mail_atelier_report_heading' => 'Nouveau compte-rendu d’atelier soumis',
    'mail_atelier_report_intro' => '**:submitter** a soumis le compte-rendu de l’atelier **:atelier** pour l’activité **:activity**.',
    'mail_atelier_report_footer' => 'Le compte-rendu est verrouillé côté encadreur. Consultez l’administration pour le détail complet.',

    'mail_attendance_reminder_subject' => 'Rappel pointage — :activity',
    'mail_attendance_reminder_heading' => 'Fin de fenêtre de pointage imminente',
    'mail_attendance_reminder_intro' => 'Il reste moins de 5 minutes pour marquer les présences de l’activité **:activity** (:event). Échéance : **:deadline**.',
    'mail_attendance_reminder_action' => 'Connectez-vous au portail encadreur ou à l’administration pour finaliser le pointage de votre atelier.',

    'mail_attendance_overdue_subject' => 'Pointage en retard — :activity',
    'mail_attendance_overdue_heading' => 'Fenêtre de pointage dépassée',
    'mail_attendance_overdue_intro' => 'La fenêtre de pointage de l’activité **:activity** (:event) est dépassée depuis **:deadline**.',
    'mail_attendance_overdue_action' => 'Vérifiez les présences enregistrées et contactez les responsables d’atelier si nécessaire.',

    'attendance_window_closed' => 'La fenêtre de pointage pour cette activité est terminée. Seul un administrateur peut encore modifier les présences.',

    'mail_staff_assignment_subject' => 'Affectation retraite — :role :target',
    'mail_staff_assignment_heading' => 'Nouvelle affectation encadrement',
    'mail_staff_assignment_intro' => 'Vous avez été désigné **:role** de **:type :label** pour la retraite des jeunes.',
    'mail_staff_assignment_body' => 'Connectez-vous à l’espace d’administration pour consulter les participants, les présences et les consignes liées à votre mission.',
    'mail_staff_assignment_button' => 'Ouvrir l’administration',
    'mail_staff_assignment_type_atelier' => 'l’atelier',
    'mail_staff_assignment_type_chambre' => 'la chambre',
    'mail_staff_assignment_atelier' => 'Atelier n° :numero',
    'mail_staff_assignment_chambre' => 'Chambre :nom',
];
