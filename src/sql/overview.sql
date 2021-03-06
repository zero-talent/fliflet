USE fiflet;
SET NAMES 'UTF8';
SELECT
  s.navn,
  COUNT( j.id_journal ) AS antall_dok,
  ROUND( AVG( DATEDIFF( j.jourdato, j.dokdato ) ) ) AS dager_jour,
  ROUND( AVG( DATEDIFF( j.pubdato, j.dokdato ) ) ) AS dager_pub
FROM
  supplier s
    INNER JOIN
      journal j
        ON ( s.id_supplier = j.id_supplier )
WHERE
  j.dokdato < j.jourdato
AND
  j.jourdato < j.pubdato
GROUP BY
  j.id_supplier
HAVING
  dager_jour < 365
ORDER BY
  j.id_supplier ASC;
