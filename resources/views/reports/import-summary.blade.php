Import du {{ optional($batch->completed_at ?? $batch->updated_at)->format('d/m/Y H:i') }} :
{{ number_format((int) $batch->created_rows, 0, ',', ' ') }} créées,
{{ number_format((int) $batch->updated_rows, 0, ',', ' ') }} mises à jour,
{{ number_format((int) $batch->skipped_rows, 0, ',', ' ') }} ignorées.
{{ number_format((int) $batch->failed_rows, 0, ',', ' ') }} anomalies ou lignes à vérifier.
