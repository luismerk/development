

SELECT DISTINCT 
	meta_key 
FROM 
	`hjwp_postmeta` 
WHERE 
	meta_key LIKE '%_zoner%'
order by meta_key
