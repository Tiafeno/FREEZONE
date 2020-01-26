<?php
/**
 * Created by IntelliJ IDEA.
 * User: you-f
 * Date: 29/04/2019
 * Time: 18:51
 */

class fzServices
{
    protected $sector_activity = [
        [
            'id' => 1,
            'name' => "Agroalimentaire"
        ],
        [
            'id' => 2,
            'name' => "Artisanat / Confection"
        ],
        [
            'id' => 3,
            'name' => "Chimie / Sciences / conditionnement"
        ],
        [
            'id' => 4,
            'name' => "Commerce / Vente / Distribution"
        ],
        [
            'id' => 5,
            'name' => "Conseiller client / Call center / Rédaction"
        ],
        [
            'id' => 6,
            'name' => "Construction / BTP / Immobilier"
        ],
        [
            'id' => 7,
            'name' => "Consultant / Enquêteur"
        ],
        [
            'id' => 8,
            'name' => "Culture / Sports / Loisirs"
        ],
        [
            'id' => 9,
            'name' => "Énergie / Environnement / Recyclage"
        ],
        [
            'id' => 10,
            'name' => "Enseignement / Formation"
        ],
        [
            'id' => 11,
            'name' => "Gestion / Comptabilité / Finance / assurances"
        ],
        [
            'id' => 12,
            'name' => "Humanitaire / Action sociale"
        ],
        [
            'id' => 13,
            'name' => "Import / Export"
        ],
        [
            'id' => 14,
            'name' => "Industrie / Ingénierie / Production"
        ],
        [
            'id' => 15,
            'name' => "Informatique / Télécommunication / Web"
        ],
        [
            'id' => 16,
            'name' => "Journalisme / Langue / Interprète"
        ],
        [
            'id' => 17,
            'name' => "Logistique / Transports / Achats"
        ],
        [
            'id' => 18,
            'name' => "Main d’œuvre / Ménage / Chauffeur"
        ],
        [
            'id' => 19,
            'name' => "Maintenance / Mécanique"
        ],
        [
            'id' => 20,
            'name' => "Management / Ressources Humaines (RH)"
        ],
        [
            'id' => 21,
            'name' => "Marketing / Communication / Médias"
        ],
        [
            'id' => 22,
            'name' => "Médecine / Santé"
        ],
        [
            'id' => 23,
            'name' => "	Minerais / minéraux / sidérurgie"
        ],
        [
            'id' => 24,
            'name' => "Qualité / Normes / Sécurité"
        ],
        [
            'id' => 25,
            'name' => "Réception / Accueil / Standard"
        ],
        [
            'id' => 26,
            'name' => "Responsable / Direction / Administration"
        ],
        [
            'id' => 27,
            'name' => "Restauration / Hôtellerie"
        ],
        [
            'id' => 28,
            'name' => "Textile / Habillement / Chaussure"
        ],
        [
            'id' => 29,
            'name' => "Tourisme / Voyage"
        ],
        [
            'id' => 30,
            'name' => "Droit / Juriste"
        ]
    ];
    public function __construct () { }
    public function get_sector_activity() {
        return $this->sector_activity;
    }


}