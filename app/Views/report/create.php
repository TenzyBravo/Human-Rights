<?php
/** @var array $categories @var array $provinces @var array $districts @var array|null $user @var array $errors @var array $old */
$preselected = (int)($_GET['category'] ?? 0);
$maxMb = (int)(\App\Core\App::config('uploads.max_bytes') / 1048576);
?>
<section class="section">
  <div class="container" style="max-width:60rem">

    <div class="text-center mb-4">
      <span class="eyebrow"><i class="bi bi-lock"></i>Confidential submission</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Report a human rights violation</h1>
      <p class="text-muted mx-auto" style="max-width:40rem">
        Take your time. Only the category, a title, a description and your consent are required —
        everything else helps but can be left blank.
      </p>
    </div>

    <?php if ($errors !== []): ?>
      <div class="alert alert-danger">
        <strong class="d-block mb-1">Please correct the following:</strong>
        <ul class="mb-0 small">
          <?php foreach ($errors as $messages): ?>
            <li><?= e($messages[0]) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card p-3 p-lg-4" data-wizard>
      <ol class="wizard-steps">
        <li class="current">1. Who is reporting</li>
        <li>2. The incident</li>
        <li>3. Evidence</li>
        <li>4. Review &amp; submit</li>
      </ol>

      <form method="post" action="<?= e(url('report')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf() ?>

        <!-- ------------------------------------------------ step 1: reporter -->
        <div class="wizard-pane active">
          <h2 class="h6 fw-semibold mb-3">Your details</h2>

          <div class="card bg-body-tertiary border-0 p-3 mb-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous"
                     name="is_anonymous" value="1" <?= !empty($old['is_anonymous']) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="is_anonymous">
                Report anonymously
              </label>
            </div>
            <p class="text-muted fs-tiny mb-0 mt-2">
              With this on, no name, phone number, email or address is stored with your report.
              You will still get a reference number to track it — write it down, because we will
              have no way to contact you or recover it for you.
            </p>
          </div>

          <div id="identityBlock">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label required" for="reporter_name">Full name</label>
                <input type="text" id="reporter_name" name="reporter_name" required maxlength="160"
                       data-summary="Reported by"
                       class="form-control<?= invalid($errors, 'reporter_name') ?>"
                       value="<?= old($old, 'reporter_name', $user ? trim($user['first_name'] . ' ' . $user['last_name']) : '') ?>">
                <?= errorFor($errors, 'reporter_name') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="reporter_phone">Phone number</label>
                <input type="tel" id="reporter_phone" name="reporter_phone" maxlength="30"
                       class="form-control<?= invalid($errors, 'reporter_phone') ?>"
                       value="<?= old($old, 'reporter_phone', $user['phone'] ?? '') ?>">
                <?= errorFor($errors, 'reporter_phone') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="reporter_email">Email address</label>
                <input type="email" id="reporter_email" name="reporter_email" maxlength="190"
                       class="form-control<?= invalid($errors, 'reporter_email') ?>"
                       value="<?= old($old, 'reporter_email', $user['email'] ?? '') ?>">
                <div class="form-text">We send your reference number and status updates here.</div>
                <?= errorFor($errors, 'reporter_email') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="reporter_nationality">Nationality</label>
                <input type="text" id="reporter_nationality" name="reporter_nationality" maxlength="80"
                       class="form-control" value="<?= old($old, 'reporter_nationality', $user['nationality'] ?? 'Zambian') ?>">
              </div>
              <div class="col-12">
                <label class="form-label" for="reporter_address">Address</label>
                <input type="text" id="reporter_address" name="reporter_address" maxlength="255"
                       class="form-control" value="<?= old($old, 'reporter_address', $user['address'] ?? '') ?>">
              </div>
            </div>
          </div>

          <?php if ($user === null): ?>
            <p class="text-muted fs-tiny mt-3 mb-0">
              <i class="bi bi-info-circle me-1"></i>
              Filing without an account is fine. <a href="<?= e(url('register')) ?>">Registering</a>
              simply keeps all your reports in one place.
            </p>
          <?php endif; ?>
        </div>

        <!-- ------------------------------------------------ step 2: incident -->
        <div class="wizard-pane">
          <h2 class="h6 fw-semibold mb-3">About the incident</h2>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label required" for="title">Short title</label>
              <input type="text" id="title" name="title" required minlength="8" maxlength="200"
                     data-summary="Title"
                     class="form-control<?= invalid($errors, 'title') ?>" value="<?= old($old, 'title') ?>"
                     placeholder="e.g. Unlawful detention at a police post">
              <?= errorFor($errors, 'title') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label required" for="urgency">Urgency</label>
              <select id="urgency" name="urgency" required class="form-select" data-summary="Urgency">
                <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical — immediate risk'] as $value => $label): ?>
                  <option value="<?= e($value) ?>"
                    <?= ($old['urgency'] ?? 'medium') === $value ? ' selected' : '' ?>>
                    <?= e($label) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">If someone is in immediate danger, also call the hotline.</div>
            </div>

            <div class="col-12">
              <label class="form-label required" for="category_id">Category of violation</label>
              <select id="category_id" name="category_id" required class="form-select<?= invalid($errors, 'category_id') ?>"
                      data-summary="Category">
                <option value="">Select the closest category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= e((string)$category['id']) ?>"
                    <?= (int)($old['category_id'] ?? $preselected) === (int)$category['id'] ? ' selected' : '' ?>>
                    <?= e($category['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?= errorFor($errors, 'category_id') ?>
            </div>

            <div class="col-12">
              <label class="form-label required" for="description">What happened?</label>
              <textarea id="description" name="description" rows="7" required minlength="30" maxlength="20000"
                        class="form-control<?= invalid($errors, 'description') ?>"
                        placeholder="Describe what happened, when, who was involved and what the effect has been. Be as specific as you can."><?= old($old, 'description') ?></textarea>
              <div class="form-text">At least 30 characters. Facts are more useful than opinions.</div>
              <?= errorFor($errors, 'description') ?>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="incident_date">Date of incident</label>
              <input type="date" id="incident_date" name="incident_date" max="<?= e(date('Y-m-d')) ?>"
                     data-summary="Incident date"
                     class="form-control<?= invalid($errors, 'incident_date') ?>" value="<?= old($old, 'incident_date') ?>">
              <?= errorFor($errors, 'incident_date') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="incident_time">Approximate time</label>
              <input type="time" id="incident_time" name="incident_time" class="form-control"
                     value="<?= old($old, 'incident_time') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="victim_count">Number of victims</label>
              <input type="number" id="victim_count" name="victim_count" min="0" max="100000"
                     data-summary="Victims" class="form-control" value="<?= old($old, 'victim_count', '1') ?>">
            </div>

            <div class="col-md-6">
              <label class="form-label" for="province_id">Province</label>
              <select id="province_id" name="province_id" class="form-select" data-summary="Province">
                <option value="">Select province</option>
                <?php foreach ($provinces as $province): ?>
                  <option value="<?= e((string)$province['id']) ?>"
                    <?= (int)($old['province_id'] ?? 0) === (int)$province['id'] ? ' selected' : '' ?>>
                    <?= e($province['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="district_id">District</label>
              <select id="district_id" name="district_id" class="form-select" data-summary="District"
                      data-districts-for="province_id" data-endpoint="<?= e(url('api/districts/__ID__')) ?>">
                <option value="">Select a province first</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label" for="location">Specific location</label>
              <input type="text" id="location" name="location" maxlength="255" class="form-control"
                     data-summary="Location" value="<?= old($old, 'location') ?>"
                     placeholder="Village, compound, street, institution or landmark">
            </div>

            <div class="col-12">
              <div class="card bg-body-tertiary border-0 p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                  <span class="fw-semibold small"><i class="bi bi-geo-alt me-1"></i>GPS coordinates (optional)</span>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="useMyLocation">
                    Use my current location
                  </button>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="visually-hidden" for="latitude">Latitude</label>
                    <input type="text" id="latitude" name="latitude" class="form-control form-control-sm"
                           placeholder="Latitude" value="<?= old($old, 'latitude') ?>">
                  </div>
                  <div class="col-6">
                    <label class="visually-hidden" for="longitude">Longitude</label>
                    <input type="text" id="longitude" name="longitude" class="form-control form-control-sm"
                           placeholder="Longitude" value="<?= old($old, 'longitude') ?>">
                  </div>
                </div>
                <div class="form-text" id="geoStatus">Only shared with the officer handling your case.</div>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label" for="persons_involved">Persons involved</label>
              <textarea id="persons_involved" name="persons_involved" rows="3" maxlength="2000"
                        class="form-control" placeholder="Names, roles or descriptions — including your relationship to the affected person"><?= old($old, 'persons_involved') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="organization_involved">Organisation involved</label>
              <input type="text" id="organization_involved" name="organization_involved" maxlength="200"
                     class="form-control" data-summary="Organisation" value="<?= old($old, 'organization_involved') ?>"
                     placeholder="Employer, institution, agency or unit">
            </div>

            <div class="col-12">
              <label class="form-label" for="witness_details">Witness details</label>
              <textarea id="witness_details" name="witness_details" rows="3" maxlength="2000"
                        class="form-control" placeholder="Anyone who saw what happened and could be contacted"><?= old($old, 'witness_details') ?></textarea>
              <div class="form-text">Please only name a witness where they are willing to be approached.</div>
            </div>
          </div>
        </div>

        <!-- ------------------------------------------------ step 3: evidence -->
        <div class="wizard-pane">
          <h2 class="h6 fw-semibold mb-3">Supporting evidence</h2>
          <p class="text-muted small">
            Photographs, video, audio, medical or police records, letters or screenshots.
            Up to <?= e((string)\App\Core\App::config('uploads.max_files')) ?> files,
            <?= e((string)$maxMb) ?> MB each. Files are stored outside the public web directory
            and released only to the officer handling your case.
          </p>

          <div>
            <div class="dropzone" data-dropzone="evidenceInput" data-max-mb="<?= e((string)$maxMb) ?>"
                 role="button" tabindex="0" aria-controls="evidenceInput">
              <i class="bi bi-cloud-arrow-up fs-2 text-primary d-block mb-2"></i>
              <div class="fw-semibold small">Drop files here, or click to browse</div>
              <div class="text-muted fs-tiny mt-1">JPG, PNG, WEBP, GIF, MP4, MOV, MP3, WAV, PDF, DOC, DOCX</div>
            </div>
            <input type="file" id="evidenceInput" name="evidence[]" class="d-none" multiple
                   accept="image/*,video/mp4,video/quicktime,audio/*,.pdf,.doc,.docx">
            <div class="d-flex flex-wrap gap-2 mt-3" data-filelist></div>
          </div>

          <div class="alert alert-warning small mt-4 mb-0">
            <i class="bi bi-shield-exclamation me-2"></i>
            Never put yourself at risk to gather evidence. A report without any attachments is
            still investigated.
          </div>
        </div>

        <!-- ------------------------------------------------- step 4: confirm -->
        <div class="wizard-pane">
          <h2 class="h6 fw-semibold mb-3">Review your report</h2>
          <div class="card bg-body-tertiary border-0 p-3 mb-4">
            <div class="row g-3" id="wizardSummary"></div>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input<?= invalid($errors, 'consent') ?>" type="checkbox"
                     id="consent" name="consent" value="1" required>
              <label class="form-check-label small" for="consent">
                <strong>Consent declaration.</strong> I confirm that the information in this report is
                true to the best of my knowledge, and I consent to it being processed and, where
                necessary, shared with the officers and authorities required to investigate it.
                I understand that knowingly submitting false information is an offence.
              </label>
              <?= errorFor($errors, 'consent') ?>
            </div>
          </div>

          <div class="alert alert-info small">
            <i class="bi bi-info-circle me-2"></i>
            On submission you receive a reference number such as <span class="mono">HR-<?= e(date('Y')) ?>-000123</span>.
            Keep it safe — it is how you track this report.
          </div>
        </div>

        <!-- navigation -->
        <div class="d-flex flex-wrap justify-content-between gap-2 mt-4 pt-3 border-top">
          <button type="button" class="btn btn-outline-secondary d-none" data-wizard-back>
            <i class="bi bi-arrow-left me-2"></i>Back
          </button>
          <div class="ms-auto d-flex gap-2">
            <a href="<?= e(url('/')) ?>" class="btn btn-link text-muted">Cancel</a>
            <button type="button" class="btn btn-primary px-4" data-wizard-next>
              Continue<i class="bi bi-arrow-right ms-2"></i>
            </button>
            <button type="submit" class="btn btn-primary px-4 d-none" data-wizard-submit>
              <i class="bi bi-shield-check me-2"></i>Submit report
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
