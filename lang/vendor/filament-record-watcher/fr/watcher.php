<?php

return [
    'pages' => [
        'my_watches' => 'Mes suivis',
    ],

    'notification' => [
        'title' => ':label modifie',
    ],

    'actions' => [
        'watch' => 'Suivre',
        'edit_watch' => 'Modifier le suivi',
        'watch_heading' => 'Suivre cet enregistrement',
        'watch_description' => 'Vous recevrez une notification dans le panel chaque fois que cet enregistrement change. Ajoutez des regles facultatives pour filtrer les notifications.',
        'watch_success' => 'Cet enregistrement est maintenant suivi.',

        'unwatch' => 'Ne plus suivre',
        'unwatch_heading' => 'Arreter le suivi de cet enregistrement',
        'unwatch_description' => 'Vous ne recevrez plus de notifications pour les changements de cet enregistrement.',
        'unwatch_success' => 'Cet enregistrement n\'est plus suivi.',

        'conditions' => 'Conditions',
        'conditions_help' => 'Toutes les regles doivent correspondre. Laissez vide pour etre notifie a chaque changement.',
        'field' => 'Champ',
        'operator' => 'Operateur',
        'value' => 'Valeur',
        'value_help' => 'Laissez vide avec l\'operateur "changed".',
        'add_rule' => 'Ajouter une regle',

        'pause' => 'Mettre en pause',
        'resume' => 'Reprendre',

        'history' => 'Historique',
        'history_heading' => 'Historique des changements',
        'close' => 'Fermer',
    ],

    'history' => [
        'empty' => 'Aucun changement n\'a encore ete enregistre pour ce suivi.',
        'system' => 'Systeme',
    ],

    'table' => [
        'type' => 'Type',
        'record' => 'Enregistrement',
        'conditions' => 'Conditions',
        'any_change' => 'Tout changement',
        'rule_count' => '{1} 1 regle|[2,*] :count regles',
        'events' => 'Evenements',
        'paused' => 'En pause',
        'since' => 'Suivi depuis',
    ],
];
