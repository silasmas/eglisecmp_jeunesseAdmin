<?php

return [

    'rules' => [
        'title' => "Règlement d'Ordre Intérieur",
        'preamble' => "Le présent Règlement d'Ordre Intérieur a pour objet de nous fixer sur les différentes clauses relatives à la discipline lors de la Grande Retraite.",
        'conclusion' => 'Que DIEU nous bénisse !',
        'articles' => [
            ['number' => 1, 'paragraphs' => ["L'entrée à la retraite est prévue pour le dimanche 9 août 2026 à 14h30 et la sortie interviendra le samedi 15 août 2026 après 13h00."]],
            ['number' => 2, 'paragraphs' => ['Il est interdit de déranger la concentration et le repos de son voisin.']],
            ['number' => 3, 'paragraphs' => ['Il est interdit de se laver avant 4h du matin et après 23h.']],
            ['number' => 4, 'paragraphs' => ['Nous sommes tous soumis au même régime alimentaire. La nourriture extérieure est interdite sur le lieu de la retraite.']],
            ['number' => 5, 'paragraphs' => ["Il est interdit aux frères d'aller aux bâtiments des sœurs et vice versa."]],
            ['number' => 6, 'paragraphs' => ['Le respect des heures de culte est obligatoire. Les différentes vacations sont :'], 'bulletPoints' => ['Dévotion matinale', 'Culte de midi', 'Culte du soir']],
            ['number' => 7, 'paragraphs' => ['Les objets ci-après sont interdits :'], 'bulletPoints' => ['Téléphone portable', 'Tablette ou tout autre gadget électronique', "Grosse somme d'argent", 'Objets de grande valeur (bijoux en or, argent, diamant, etc.)', 'Documents à caractère pornographique', 'Objets tranchants (couteau, miroir, lame, aiguille, épingle, etc.)', 'Habits qui exposent le corps (dos nu, décalé, kata fumbwa, décolleté plongeant)', 'Toute sorte de drogue ou stimulant']],
            ['number' => 8, 'paragraphs' => ['Seules les personnes portant les chasubles sont autorisées à prier pour les membres et à leur imposer les mains.']],
            ['number' => 9, 'paragraphs' => ['Il est obligatoire pour tout le monde de rester au lieu de culte pendant le culte.']],
            ['number' => 10, 'paragraphs' => ["Il est interdit d'aller manger dans un atelier autre que le sien."]],
            ['number' => 11, 'paragraphs' => ['Il est interdit de se retrouver :'], 'bulletPoints' => ["dans un autre atelier pendant les heures d'ateliers,", 'dans une chambre autre que la sienne.']],
            ['number' => 12, 'paragraphs' => ['Il est interdit de manger dans la chambre.']],
            ['number' => 13, 'paragraphs' => ['Les encadrants et chefs de chambres sont des représentants de la jeunesse ; ils méritent notre respect.']],
            ['number' => 14, 'paragraphs' => ['Le respect mutuel est de stricte observance.']],
            ['number' => 15, 'paragraphs' => ["Tous les médicaments doivent être consignés à l'infirmerie et pris en présence d'un corps médical."]],
            ['number' => 16, 'paragraphs' => ["Toute sortie doit être signalée aux chefs de chambres et encadrants d'ateliers."]],
            ['number' => 17, 'paragraphs' => ['Les déchets doivent être jetés dans les corbeilles mises à disposition. Veillons à la propreté du site.']],
            ['number' => 18, 'paragraphs' => ["Après utilisation des sanitaires, veillons à l'aisance de la personne suivante."]],
            ['number' => 19, 'paragraphs' => ['Les externes sont tenus de suivre les orientations des protocoles.']],
            ['number' => 20, 'paragraphs' => ['Le non-respect du règlement expose le/la concerné(e) à un renvoi sans délai du lieu de la retraite.']],
        ],
    ],

    'items' => [
        'notice' => [
            'Le non-respect de ce règlement expose le concerné à un renvoi sans délai du lieu de la retraite si un seul objet défendu se retrouvait dans ses affaires.',
            "Le silence est le mot d'ordre pour tous.",
            'Tous nous sommes soumis au même régime alimentaire.',
        ],
        'required' => [
            'Reçu ou preuve de paiement',
            'Matelas ou mousse',
            'Draps ou couverture',
            'Habits de rechange',
            'Seau',
            'Savon',
            'Brosse à dents',
            'Dentifrice',
            'Ustensiles de table',
            'Bible',
            'Carnet ou bloc-notes',
            'Stylo',
            'Déodorant ou eau de parfum',
        ],
        'prohibited' => [
            'Téléphone portable',
            'Tablette ou autre gadget électronique',
            "Grosse somme d'argent",
            'Objets de grande valeur (or, argent, diamant, etc.)',
            'Documents à caractère pornographique',
            'Drogue ou stimulant',
            'Nourriture ou sucrerie',
            'Objets tranchants (couteau, lame, aiguille, etc.)',
            'Habits exposant le corps (dos nu, décolleté, etc.)',
        ],
    ],

];
