SELECT
    cl.clt_m4_id AS m4_number,
    o.order_date AS date_of_service,
    c.clinic_name,
    cl.payer,
    o.med_type
FROM tblorder o
JOIN tblclient cl
    ON o.clt_id = cl.clt_id AND o.global_site_id = cl.global_site_id
JOIN tblclinic c
    ON o.global_site_id = c.global_site_id
WHERE
    o.med_type LIKE 'b%'
    AND o.order_date >= '202i4-04-01'
    AND o.order_date < '2026-01-01';

