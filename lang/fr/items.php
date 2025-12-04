<?php

declare(strict_types=1);

return [
    // Model labels
    'item' => 'Article',
    'items' => 'Articles',

    // Fields
    'name' => 'Nom',
    'reference' => 'Référence',
    'description' => 'Description',
    'supplier' => 'Fournisseur',
    'supplier_reference' => 'Référence fournisseur',
    'purchase_price' => 'Prix d\'achat',
    'stock' => 'Stock',
    'current_stock' => 'Stock actuel',
    'stock_status' => 'État du stock',
    'materials' => 'Matériels',
    'recipients' => 'Destinataires d\'alerte',

    // Stock statuses
    'stock_ok' => 'OK',
    'stock_warning' => 'Avertissement',
    'stock_critical' => 'Critique',
    'stock_empty' => 'Vide',

    // Alerts
    'warning_enabled' => 'Alerte d\'avertissement activée',
    'warning_minimum' => 'Seuil d\'avertissement',
    'critical_enabled' => 'Alerte critique activée',
    'critical_minimum' => 'Seuil critique',

    // Movements
    'movement' => 'Mouvement',
    'movements' => 'Mouvements',
    'movement_type' => 'Type',
    'movement_entry' => 'Entrée',
    'movement_exit' => 'Sortie',
    'movement_quantity' => 'Quantité',
    'movement_date' => 'Date',
    'movement_notes' => 'Notes',
    'entry_total' => 'Total des entrées',
    'exit_total' => 'Total des sorties',
    'entry_count' => 'Nombre d\'entrées',
    'exit_count' => 'Nombre de sorties',

    // Invoice
    'invoice_number' => 'Numéro de facture',
    'invoice_date' => 'Date de facture',
    'unit_price' => 'Prix unitaire',
    'total_price' => 'Prix total',

    // Actions
    'create' => 'Créer un article',
    'edit' => 'Modifier l\'article',
    'delete' => 'Supprimer l\'article',
    'view' => 'Voir l\'article',
    'add_movement' => 'Ajouter un mouvement',
    'add_entry' => 'Ajouter une entrée',
    'add_exit' => 'Ajouter une sortie',
    'generate_qr' => 'Générer le QR Code',
    'download_qr' => 'Télécharger le QR Code',
    'print_qr' => 'Imprimer le QR Code',

    // Charts
    'monthly_movements' => 'Mouvements mensuels',
    'price_evolution' => 'Évolution du prix',
    'entries' => 'Entrées',
    'exits' => 'Sorties',
    'price' => 'Prix',

    // Messages
    'created' => 'Article créé avec succès.',
    'updated' => 'Article mis à jour avec succès.',
    'deleted' => 'Article supprimé avec succès.',
    'cannot_delete' => 'Impossible de supprimer un article avec des mouvements. Supprimez d\'abord tous les mouvements.',
    'movement_created' => 'Mouvement créé avec succès.',
    'stock_alert_critical' => 'Alerte stock critique pour :item',
    'stock_alert_warning' => 'Alerte stock avertissement pour :item',
    'stock_below_critical' => 'Le stock est en dessous du seuil critique (:current/:minimum)',
    'stock_below_warning' => 'Le stock est en dessous du seuil d\'avertissement (:current/:minimum)',

    // Validation
    'reference_unique' => 'Cette référence existe déjà pour ce site.',
    'quantity_exceeds_stock' => 'La quantité dépasse le stock disponible.',
];
