-- ============================================================================
-- HRRAMS seed data. Run AFTER schema.sql
--   mysql -u root -p hrrams < database/schema.sql
--   mysql -u root -p hrrams < database/seed.sql
-- Demo passwords (CHANGE IMMEDIATELY IN PRODUCTION):
--   admin@hrrams.test      Admin@12345
--   supervisor@hrrams.test Super@12345
--   officer@hrrams.test    Officer@12345
--   awareness@hrrams.test  Aware@12345
--   citizen@hrrams.test    Citizen@12345
-- ============================================================================
USE hrrams;

-- ------------------------------------------------------------------- roles
INSERT INTO roles (slug, name, description, is_staff) VALUES
 ('citizen',    'Registered Citizen / Reporter', 'Submits and tracks reports', 0),
 ('officer',    'Human Rights Officer',          'Investigates assigned cases', 1),
 ('supervisor', 'Supervisor',                    'Assigns, approves and closes cases', 1),
 ('awareness',  'Awareness Officer',             'Manages campaigns and resources', 1),
 ('admin',      'System Administrator',          'Full system access', 1);

-- ------------------------------------------------------------- permissions
INSERT INTO permissions (slug, name, module) VALUES
 ('report.create','Submit report','reports'),
 ('report.view_own','View own reports','reports'),
 ('report.view_any','View any report','reports'),
 ('report.delete','Delete report','reports'),
 ('case.view_assigned','View assigned cases','cases'),
 ('case.view_any','View any case','cases'),
 ('case.update','Update investigation','cases'),
 ('case.note','Add case notes','cases'),
 ('case.evidence','Upload evidence','cases'),
 ('case.interview','Schedule interviews','cases'),
 ('case.assign','Assign / reassign cases','cases'),
 ('case.approve','Approve investigations','cases'),
 ('case.escalate','Escalate cases','cases'),
 ('case.close','Close cases','cases'),
 ('awareness.manage','Manage campaigns & resources','awareness'),
 ('awareness.analytics','View campaign analytics','awareness'),
 ('event.manage','Manage events','awareness'),
 ('dashboard.department','View departmental statistics','dashboard'),
 ('dashboard.admin','View admin dashboard','dashboard'),
 ('user.manage','Manage users','admin'),
 ('role.manage','Manage roles & permissions','admin'),
 ('category.manage','Manage report categories','admin'),
 ('settings.manage','Manage system settings','admin'),
 ('audit.view','View audit logs','admin'),
 ('backup.manage','Backup / restore database','admin'),
 ('report.export','Generate & export reports','reporting');

-- role_permissions --------------------------------------------------------
-- citizen
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.slug IN ('report.create','report.view_own')
WHERE r.slug='citizen';
-- officer
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.slug IN ('report.view_any','case.view_assigned','case.update','case.note',
                'case.evidence','case.interview','report.export')
WHERE r.slug='officer';
-- supervisor
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.slug IN ('report.view_any','case.view_any','case.view_assigned','case.update','case.note',
                'case.assign','case.approve','case.escalate','case.close',
                'dashboard.department','report.export')
WHERE r.slug='supervisor';
-- awareness officer
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
  ON p.slug IN ('awareness.manage','awareness.analytics','event.manage','report.export')
WHERE r.slug='awareness';
-- admin: everything
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON 1=1 WHERE r.slug='admin';

-- --------------------------------------------------------------- provinces
INSERT INTO provinces (name, code) VALUES
 ('Central','CEN'),('Copperbelt','CPB'),('Eastern','EAS'),('Luapula','LUA'),
 ('Lusaka','LSK'),('Muchinga','MUC'),('Northern','NOR'),('North-Western','NWE'),
 ('Southern','SOU'),('Western','WES');

INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Kabwe' d UNION SELECT 'Chibombo' UNION SELECT 'Mkushi') x
WHERE name='Central';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Ndola' d UNION SELECT 'Kitwe' UNION SELECT 'Chingola' UNION SELECT 'Luanshya') x
WHERE name='Copperbelt';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Lusaka' d UNION SELECT 'Chongwe' UNION SELECT 'Kafue' UNION SELECT 'Chilanga') x
WHERE name='Lusaka';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Chipata' d UNION SELECT 'Petauke' UNION SELECT 'Katete') x
WHERE name='Eastern';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Livingstone' d UNION SELECT 'Choma' UNION SELECT 'Mazabuka') x
WHERE name='Southern';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Mansa' d UNION SELECT 'Samfya') x WHERE name='Luapula';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Chinsali' d UNION SELECT 'Mpika') x WHERE name='Muchinga';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Kasama' d UNION SELECT 'Mbala') x WHERE name='Northern';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Solwezi' d UNION SELECT 'Mwinilunga') x WHERE name='North-Western';
INSERT INTO districts (province_id, name)
SELECT id, d FROM provinces, (SELECT 'Mongu' d UNION SELECT 'Senanga') x WHERE name='Western';

-- -------------------------------------------------------------- categories
INSERT INTO report_categories (name, slug, description, icon) VALUES
 ('Arbitrary Arrest & Detention','arbitrary-arrest','Unlawful arrest, detention without charge or trial','bi-lock'),
 ('Torture & Inhuman Treatment','torture','Physical or psychological ill-treatment','bi-exclamation-octagon'),
 ('Gender-Based Violence','gbv','Violence directed at a person because of their gender','bi-gender-ambiguous'),
 ('Child Rights Violation','child-rights','Child labour, abuse, neglect, denial of education','bi-people'),
 ('Discrimination','discrimination','Unfair treatment on any protected ground','bi-slash-circle'),
 ('Freedom of Expression','expression','Suppression of speech, press or assembly','bi-megaphone'),
 ('Labour Rights Violation','labour-rights','Unsafe work, unpaid wages, unfair dismissal','bi-briefcase'),
 ('Right to Education','education','Denial of access to education','bi-book'),
 ('Right to Health','health','Denial of access to healthcare services','bi-heart-pulse'),
 ('Land & Property Rights','land-rights','Forced eviction, unlawful dispossession','bi-house'),
 ('Disability Rights','disability-rights','Exclusion or abuse of persons with disabilities','bi-universal-access'),
 ('Other Violation','other','Any violation not covered above','bi-three-dots');

-- ------------------------------------------------------------------- users
INSERT INTO users (first_name,last_name,email,phone,password_hash,status,email_verified_at,province_id,nationality) VALUES
 ('System','Administrator','admin@hrrams.test','+260970000001','$2y$10$dlPeP/Zb/dstDNXfJe.eHepPgc.ahCNpBSdummqSgYuSIIcwRapYu','active',NOW(),5,'Zambian'),
 ('Grace','Mulenga','supervisor@hrrams.test','+260970000002','$2y$10$.rq5O1F8A95SIIfmJ31jsu.OC3DabyTnBatlWDJEijSl9ccWzHaFu','active',NOW(),5,'Zambian'),
 ('Daniel','Phiri','officer@hrrams.test','+260970000003','$2y$10$5PmGPXkxOiBMFKL.xmm2k.B2LmwKoYZsysEsKERXIReU56jayHZcS','active',NOW(),2,'Zambian'),
 ('Naomi','Banda','awareness@hrrams.test','+260970000004','$2y$10$xRfMmx7e4d4T4OW6tSrLWerx2OBvAsuk07Z/3189mz3mwpX2ie2QW','active',NOW(),5,'Zambian'),
 ('Joseph','Tembo','citizen@hrrams.test','+260970000005','$2y$10$mxSC1RiysBc8mnzhIvsZD.hrxVek0Hoan3ihlxsp.509iBmdBfwXa','active',NOW(),9,'Zambian');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.slug =
  CASE u.email
    WHEN 'admin@hrrams.test'      THEN 'admin'
    WHEN 'supervisor@hrrams.test' THEN 'supervisor'
    WHEN 'officer@hrrams.test'    THEN 'officer'
    WHEN 'awareness@hrrams.test'  THEN 'awareness'
    ELSE 'citizen'
  END;

-- ---------------------------------------------------------------- settings
INSERT INTO settings (skey, svalue, stype, group_name) VALUES
 ('site_name','HRRAMS','string','general'),
 ('site_tagline','Human Rights Reporting & Awareness Management System','string','general'),
 ('contact_email','info@hrrams.test','string','general'),
 ('contact_phone','+260 211 000 000','string','general'),
 ('hotline','933','string','general'),
 ('session_timeout_minutes','30','int','security'),
 ('max_login_attempts','5','int','security'),
 ('lockout_minutes','15','int','security'),
 ('password_min_length','10','int','security'),
 ('require_email_verification','1','bool','security'),
 ('max_upload_mb','20','int','uploads'),
 ('sla_days_default','30','int','cases'),
 ('mail_from_name','HRRAMS Notifications','string','mail'),
 ('mail_from_email','no-reply@hrrams.test','string','mail');

-- -------------------------------------------------------- awareness content
INSERT INTO awareness_campaigns (title,slug,type,summary,body,status,starts_on,ends_on,published_at,created_by) VALUES
 ('Know Your Rights: 16 Days of Activism','know-your-rights-16-days','campaign',
  'A nationwide campaign against gender-based violence running every November to December.',
  'The 16 Days of Activism against Gender-Based Violence is an international campaign observed from 25 November to 10 December. This campaign brings together communities, schools and civic leaders across all ten provinces to raise awareness, share reporting channels and support survivors.',
  'published','2026-11-25','2026-12-10',NOW(),4),
 ('Every Child Has a Right to School','right-to-school','campaign',
  'Community outreach on the right to free basic education and how to report denial of access.',
  'Education is a right, not a privilege. This campaign works with traditional leaders and parent-teacher associations to identify and resolve barriers keeping children out of classrooms.',
  'published','2026-03-01','2026-06-30',NOW(),4),
 ('Commission Opens Three New Provincial Desks','new-provincial-desks','news',
  'Reporting desks are now open in Muchinga, North-Western and Western provinces.',
  'Citizens in Chinsali, Solwezi and Mongu can now file complaints in person, in addition to the online reporting channel available on this platform.',
  'published',NULL,NULL,NOW(),4),
 ('Survivor Story: Justice After Two Years','survivor-story-justice','success_story',
  'How a community-led complaint resulted in the reinstatement of 42 unlawfully dismissed workers.',
  'In 2024 a group of 42 workers approached the Commission after being dismissed without notice or terminal benefits. Following an eleven-month investigation, mediation and a formal recommendation, all 42 were reinstated with back pay.',
  'published',NULL,NULL,NOW(),4);

INSERT INTO educational_resources (title,slug,resource_type,description,external_url,is_published,created_by) VALUES
 ('Universal Declaration of Human Rights (Full Text)','udhr','document',
  'The 30 articles of the UDHR, adopted by the UN General Assembly in 1948.',
  'https://www.un.org/en/about-us/universal-declaration-of-human-rights',1,4),
 ('Bill of Rights - Constitution of Zambia','bill-of-rights-zambia','document',
  'Part III of the Constitution of Zambia: protection of fundamental rights and freedoms.',NULL,1,4),
 ('How to Document a Human Rights Violation','documenting-violations','training',
  'A practical guide for community monitors: what to record, how to store evidence safely, and how to protect witnesses.',NULL,1,4),
 ('Child Protection Referral Pathways','child-protection-pathways','document',
  'Who to contact, in what order, when a child is at immediate risk.',NULL,1,4);

INSERT INTO events (title,description,venue,province_id,starts_at,ends_at,status,created_by) VALUES
 ('Community Legal Aid Clinic - Kanyama','Free walk-in legal advice and complaint registration.','Kanyama Community Hall',5,'2026-09-05 09:00:00','2026-09-05 16:00:00','published',4),
 ('Human Rights Training for Paralegals','Two-day certified training on complaint handling and documentation.','Ndola Civic Centre',2,'2026-10-14 08:30:00','2026-10-15 17:00:00','published',4);
