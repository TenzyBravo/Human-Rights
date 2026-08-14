<?php
/** @var array $case @var array $timeline @var array $attachments @var array $officers @var array $statuses */
$overdue = $case['due_at'] !== null && $case['resolved_at'] === null && strtotime((string)$case['due_at']) < time();
$closed  = in_array($case['status'], ['resolved', 'closed', 'rejected'], true);
$maxMb   = (int)(\App\Core\App::config('uploads.max_bytes') / 1048576);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
  <div>
    <a href="<?= e(url('cases')) ?>" class="small text-muted"><i class="bi bi-arrow-left me-1"></i>Back to cases</a>
    <h1 class="h4 fw-semibold mt-2 mb-1"><?= e($case['title']) ?></h1>
    <p class="text-muted small mb-0">
      Case <span class="mono fw-semibold"><?= e($case['case_number']) ?></span>
      · Report <span class="mono"><?= e($case['reference_no']) ?></span>
      · Opened <?= e(d($case['opened_at'])) ?>
    </p>
  </div>
  <div class="d-flex flex-wrap gap-2 align-items-center">
    <span class="badge badge-soft bg-<?= e(urgencyColor((string)$case['priority'])) ?>">
      <?= e(ucfirst((string)$case['priority'])) ?> priority
    </span>
    <span class="badge badge-soft bg-<?= e(statusColor((string)$case['status'])) ?> fs-6">
      <?= e(statusLabel((string)$case['status'])) ?>
    </span>
  </div>
</div>

<?php if ($overdue): ?>
  <div class="alert alert-danger d-flex align-items-center gap-2">
    <i class="bi bi-alarm-fill"></i>
    <span class="small">
      This case passed its service deadline on <strong><?= e(dt($case['due_at'])) ?></strong>.
      Record progress or escalate it.
    </span>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- ------------------------------------------------------------ left column -->
  <div class="col-lg-8">

    <!-- complaint detail -->
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">The complaint</h2>
      <p class="small" style="white-space:pre-line"><?= e($case['description']) ?></p>
      <hr class="hr-soft">
      <div class="row g-3 small">
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">Category</div>
          <div class="fw-semibold"><?= e($case['category_name']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">Incident date &amp; time</div>
          <div class="fw-semibold">
            <?= $case['incident_date'] ? e(d($case['incident_date'])) : 'Not specified' ?>
            <?= $case['incident_time'] ? ' · ' . e(substr((string)$case['incident_time'], 0, 5)) : '' ?>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">Location</div>
          <div class="fw-semibold"><?= e($case['location'] ?: 'Not specified') ?></div>
          <?php if ($case['province_name']): ?>
            <div class="text-muted fs-tiny">
              <?= e($case['district_name'] ? $case['district_name'] . ', ' : '') ?><?= e($case['province_name']) ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">GPS coordinates</div>
          <?php if ($case['latitude'] !== null && $case['longitude'] !== null): ?>
            <div class="fw-semibold mono"><?= e((string)$case['latitude']) ?>, <?= e((string)$case['longitude']) ?></div>
            <a class="fs-tiny" target="_blank" rel="noopener noreferrer"
               href="https://www.openstreetmap.org/?mlat=<?= e((string)$case['latitude']) ?>&amp;mlon=<?= e((string)$case['longitude']) ?>#map=15/<?= e((string)$case['latitude']) ?>/<?= e((string)$case['longitude']) ?>">
              View on map <i class="bi bi-box-arrow-up-right"></i>
            </a>
          <?php else: ?>
            <div class="text-muted">Not captured</div>
          <?php endif; ?>
        </div>
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">Number of victims</div>
          <div class="fw-semibold"><?= e((string)$case['victim_count']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="text-muted fs-tiny">Organisation involved</div>
          <div class="fw-semibold"><?= e($case['organization_involved'] ?: '—') ?></div>
        </div>
        <?php if ($case['persons_involved']): ?>
          <div class="col-12">
            <div class="text-muted fs-tiny">Persons involved</div>
            <div style="white-space:pre-line"><?= e($case['persons_involved']) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($case['witness_details']): ?>
          <div class="col-12">
            <div class="text-muted fs-tiny">Witness details</div>
            <div style="white-space:pre-line"><?= e($case['witness_details']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- actions -->
    <?php if (!$closed): ?>
      <div class="card mt-4">
        <div class="p-3 border-bottom">
          <h2 class="h6 fw-semibold mb-0">Record an action</h2>
        </div>
        <div class="p-3">
          <ul class="nav nav-pills nav-fill gap-1 mb-3" role="tablist">
            <?php
            $tabs = [
                ['note',      'Case note',        'bi-journal-text',   can('case.note')],
                ['evidence',  'Upload findings',  'bi-paperclip',      can('case.evidence')],
                ['interview', 'Schedule interview','bi-calendar-plus', can('case.interview')],
                ['info',      'Request info',     'bi-question-circle',can('case.update')],
                ['status',    'Change status',    'bi-arrow-repeat',   can('case.update')],
            ];
            $first = true;
            foreach ($tabs as [$key, $label, $icon, $allowed]):
                if (!$allowed) { continue; } ?>
              <li class="nav-item" role="presentation">
                <button class="nav-link<?= $first ? ' active' : '' ?> btn-sm" data-bs-toggle="pill"
                        data-bs-target="#tab-<?= e($key) ?>" type="button" role="tab">
                  <i class="bi <?= e($icon) ?> me-1"></i><span class="d-none d-md-inline"><?= e($label) ?></span>
                </button>
              </li>
            <?php $first = false; endforeach; ?>
          </ul>

          <div class="tab-content">
            <?php $first = true; ?>

            <?php if (can('case.note')): ?>
              <div class="tab-pane fade<?= $first ? ' show active' : '' ?>" id="tab-note" role="tabpanel">
                <form method="post" action="<?= e(url('cases/' . $case['id'] . '/note')) ?>">
                  <?= csrf() ?>
                  <div class="row g-2">
                    <div class="col-md-5">
                      <label class="form-label" for="note_title">Heading</label>
                      <input type="text" id="note_title" name="title" maxlength="200" class="form-control form-control-sm"
                             placeholder="e.g. Site visit conducted">
                    </div>
                    <div class="col-12">
                      <label class="form-label required" for="note_body">Note</label>
                      <textarea id="note_body" name="body" rows="4" required minlength="3" maxlength="5000"
                                class="form-control form-control-sm"
                                placeholder="What did you do, find or decide?"></textarea>
                    </div>
                    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="share_with_reporter"
                               name="share_with_reporter" value="1">
                        <label class="form-check-label small" for="share_with_reporter">
                          Visible to the complainant
                        </label>
                      </div>
                      <button class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add note</button>
                    </div>
                  </div>
                </form>
              </div>
              <?php $first = false; ?>
            <?php endif; ?>

            <?php if (can('case.evidence')): ?>
              <div class="tab-pane fade<?= $first ? ' show active' : '' ?>" id="tab-evidence" role="tabpanel">
                <form method="post" action="<?= e(url('cases/' . $case['id'] . '/evidence')) ?>"
                      enctype="multipart/form-data">
                  <?= csrf() ?>
                  <div class="row g-2">
                    <div class="col-md-6">
                      <label class="form-label" for="ev_title">Title</label>
                      <input type="text" id="ev_title" name="title" maxlength="200" class="form-control form-control-sm"
                             placeholder="e.g. Medical report from clinic">
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="ev_body">Description</label>
                      <textarea id="ev_body" name="body" rows="2" maxlength="2000"
                                class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="col-12">
                      <div class="dropzone py-3" data-dropzone="caseEvidence" data-max-mb="<?= e((string)$maxMb) ?>"
                           role="button" tabindex="0">
                        <i class="bi bi-cloud-arrow-up text-primary d-block mb-1"></i>
                        <span class="small">Drop files or click to browse (max <?= e((string)$maxMb) ?> MB each)</span>
                      </div>
                      <input type="file" id="caseEvidence" name="evidence[]" class="d-none" multiple>
                      <div class="d-flex flex-wrap gap-2 mt-2" data-filelist></div>
                    </div>
                    <div class="col-12 text-end">
                      <button class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Attach to case</button>
                    </div>
                  </div>
                </form>
              </div>
              <?php $first = false; ?>
            <?php endif; ?>

            <?php if (can('case.interview')): ?>
              <div class="tab-pane fade<?= $first ? ' show active' : '' ?>" id="tab-interview" role="tabpanel">
                <form method="post" action="<?= e(url('cases/' . $case['id'] . '/interview')) ?>">
                  <?= csrf() ?>
                  <div class="row g-2">
                    <div class="col-md-7">
                      <label class="form-label required" for="iv_title">Who is being interviewed?</label>
                      <input type="text" id="iv_title" name="title" required maxlength="200"
                             class="form-control form-control-sm" placeholder="e.g. Interview with complainant">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label required" for="scheduled_at">Date &amp; time</label>
                      <input type="datetime-local" id="scheduled_at" name="scheduled_at" required
                             class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                      <label class="form-label" for="iv_body">Notes / venue</label>
                      <textarea id="iv_body" name="body" rows="2" maxlength="2000"
                                class="form-control form-control-sm"></textarea>
                    </div>
                    <div class="col-12 text-end">
                      <button class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i>Schedule</button>
                    </div>
                  </div>
                </form>
              </div>
              <?php $first = false; ?>
            <?php endif; ?>

            <?php if (can('case.update')): ?>
              <div class="tab-pane fade<?= $first ? ' show active' : '' ?>" id="tab-info" role="tabpanel">
                <form method="post" action="<?= e(url('cases/' . $case['id'] . '/request-info')) ?>">
                  <?= csrf() ?>
                  <label class="form-label required" for="info_body">What do you need from the complainant?</label>
                  <textarea id="info_body" name="body" rows="4" required minlength="10" maxlength="3000"
                            class="form-control form-control-sm mb-2"
                            placeholder="Be specific about what is missing and why it matters."></textarea>
                  <?php if ((int)$case['is_anonymous'] === 1): ?>
                    <div class="alert alert-warning small py-2">
                      <i class="bi bi-exclamation-triangle me-1"></i>
                      This report was filed anonymously — the request will be logged but cannot be delivered.
                    </div>
                  <?php endif; ?>
                  <div class="text-end">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Send request</button>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="tab-status" role="tabpanel">
                <form method="post" action="<?= e(url('cases/' . $case['id'] . '/status')) ?>">
                  <?= csrf() ?>
                  <div class="row g-2">
                    <div class="col-md-5">
                      <label class="form-label required" for="new_status">New status</label>
                      <select id="new_status" name="status" required class="form-select form-select-sm">
                        <?php foreach ($statuses as $status): ?>
                          <?php if (in_array($status, ['resolved', 'closed', 'rejected'], true) && !can('case.close')) { continue; } ?>
                          <option value="<?= e($status) ?>"<?= $case['status'] === $status ? ' selected' : '' ?>>
                            <?= e(statusLabel($status)) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-7">
                      <label class="form-label" for="status_note">Reason / note</label>
                      <input type="text" id="status_note" name="note" maxlength="1000" class="form-control form-control-sm">
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                      <?php if (can('case.escalate')): ?>
                        <button class="btn btn-outline-danger btn-sm" type="submit"
                                formaction="<?= e(url('cases/' . $case['id'] . '/escalate')) ?>"
                                data-confirm="Escalate this case to senior review?">
                          <i class="bi bi-arrow-up-circle me-1"></i>Escalate
                        </button>
                      <?php endif; ?>
                      <button class="btn btn-primary btn-sm">
                        <i class="bi bi-check2 me-1"></i>Update status
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- closure -->
    <?php if (can('case.close') && !in_array($case['status'], ['closed', 'rejected'], true)): ?>
      <div class="card mt-4">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
          <h2 class="h6 fw-semibold mb-0">Conclude the case</h2>
          <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#closeBlock">
            <i class="bi bi-chevron-down"></i>
          </button>
        </div>
        <div class="collapse" id="closeBlock">
          <form method="post" action="<?= e(url('cases/' . $case['id'] . '/close')) ?>" class="p-3">
            <?= csrf() ?>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label required" for="outcome">Outcome</label>
                <select id="outcome" name="outcome" required class="form-select form-select-sm">
                  <option value="resolved">Resolved — remedy achieved</option>
                  <option value="closed">Closed — no further action</option>
                  <option value="rejected">Rejected — outside mandate</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label required" for="resolution_summary">Resolution summary</label>
                <textarea id="resolution_summary" name="resolution_summary" rows="4" required
                          minlength="20" maxlength="5000" class="form-control form-control-sm"
                          placeholder="Findings, remedy recommended or granted, and any referral made."></textarea>
                <div class="form-text">This summary is shared with the complainant.</div>
              </div>
              <div class="col-12 text-end">
                <button class="btn btn-success btn-sm" data-confirm="Conclude this case? The complainant will be notified.">
                  <i class="bi bi-check2-circle me-1"></i>Conclude case
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- timeline -->
    <div class="card p-4 mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Investigation timeline</h2>
        <span class="text-muted fs-tiny"><?= e((string)count($timeline)) ?> entr<?= count($timeline) === 1 ? 'y' : 'ies' ?></span>
      </div>
      <?php if ($timeline === []): ?>
        <div class="empty-state"><i class="bi bi-clock-history"></i>No activity recorded on this case yet.</div>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($timeline as $entry): ?>
            <div class="timeline-item type-<?= e($entry['type']) ?>">
              <div class="d-flex flex-wrap justify-content-between gap-2">
                <span class="fw-semibold small">
                  <?= e($entry['title'] ?: statusLabel((string)$entry['type'])) ?>
                  <?php if ((int)$entry['is_internal'] === 0): ?>
                    <span class="badge badge-soft bg-info ms-1">Shared</span>
                  <?php endif; ?>
                </span>
                <span class="text-muted fs-tiny">
                  <?= e($entry['author'] ?: 'System') ?> · <?= e(dt($entry['created_at'])) ?>
                </span>
              </div>
              <?php if ($entry['type'] === 'status_change' && $entry['old_value']): ?>
                <p class="small mb-1">
                  <span class="badge badge-soft bg-secondary"><?= e(statusLabel((string)$entry['old_value'])) ?></span>
                  <i class="bi bi-arrow-right mx-1"></i>
                  <span class="badge badge-soft bg-<?= e(statusColor((string)$entry['new_value'])) ?>">
                    <?= e(statusLabel((string)$entry['new_value'])) ?>
                  </span>
                </p>
              <?php endif; ?>
              <?php if ($entry['scheduled_at']): ?>
                <p class="small mb-1">
                  <i class="bi bi-calendar-event me-1"></i>Scheduled for
                  <strong><?= e(dt($entry['scheduled_at'])) ?></strong>
                </p>
              <?php endif; ?>
              <?php if ($entry['body']): ?>
                <p class="text-muted small mb-0" style="white-space:pre-line"><?= e($entry['body']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ----------------------------------------------------------- right column -->
  <div class="col-lg-4">

    <!-- assignment -->
    <div class="card p-4">
      <h2 class="h6 fw-semibold mb-3">Assignment</h2>
      <div class="d-flex align-items-center gap-3 mb-3">
        <span class="avatar" style="width:42px;height:42px">
          <?= e($case['officer_name'] ? initials((string)$case['officer_name']) : '—') ?>
        </span>
        <div class="small">
          <div class="fw-semibold"><?= e($case['officer_name'] ?: 'Not yet assigned') ?></div>
          <div class="text-muted fs-tiny"><?= e($case['officer_email'] ?: 'Awaiting supervisor allocation') ?></div>
        </div>
      </div>
      <dl class="row small mb-0">
        <dt class="col-6 text-muted fw-normal">Supervisor</dt>
        <dd class="col-6"><?= e($case['supervisor_name'] ?: '—') ?></dd>
        <dt class="col-6 text-muted fw-normal">Due by</dt>
        <dd class="col-6<?= $overdue ? ' text-danger fw-semibold' : '' ?>"><?= e(d($case['due_at'])) ?></dd>
        <?php if ($case['resolved_at']): ?>
          <dt class="col-6 text-muted fw-normal">Resolved</dt>
          <dd class="col-6"><?= e(d($case['resolved_at'])) ?></dd>
        <?php endif; ?>
      </dl>

      <?php if (can('case.assign') && !$closed): ?>
        <hr class="hr-soft">
        <form method="post" action="<?= e(url('cases/' . $case['id'] . '/assign')) ?>">
          <?= csrf() ?>
          <label class="form-label" for="officer_id">
            <?= $case['officer_id'] ? 'Reassign to' : 'Assign to' ?>
          </label>
          <select id="officer_id" name="officer_id" required class="form-select form-select-sm mb-2">
            <option value="">Select an officer</option>
            <?php foreach ($officers as $officer): ?>
              <option value="<?= e((string)$officer['id']) ?>"
                <?= (int)$case['officer_id'] === (int)$officer['id'] ? ' selected' : '' ?>>
                <?= e($officer['first_name'] . ' ' . $officer['last_name']) ?>
                (<?= e((string)$officer['open_cases']) ?> open)
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="note" maxlength="500" class="form-control form-control-sm mb-2"
                 placeholder="Instruction to the officer (optional)">
          <button class="btn btn-primary btn-sm w-100">
            <i class="bi bi-person-check me-1"></i><?= $case['officer_id'] ? 'Reassign case' : 'Assign case' ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- complainant -->
    <div class="card p-4 mt-4">
      <h2 class="h6 fw-semibold mb-3">Complainant</h2>
      <?php if ((int)$case['is_anonymous'] === 1): ?>
        <div class="d-flex gap-2 align-items-start">
          <i class="bi bi-eye-slash text-muted fs-5"></i>
          <p class="text-muted small mb-0">
            Filed anonymously. No identifying details were collected, and the complainant
            cannot be contacted about this case.
          </p>
        </div>
      <?php else: ?>
        <dl class="row small mb-0">
          <dt class="col-4 text-muted fw-normal">Name</dt>
          <dd class="col-8"><?= e($case['reporter_name'] ?: '—') ?></dd>
          <dt class="col-4 text-muted fw-normal">Email</dt>
          <dd class="col-8 text-break"><?= e($case['reporter_email'] ?: '—') ?></dd>
          <dt class="col-4 text-muted fw-normal">Phone</dt>
          <dd class="col-8"><?= e($case['reporter_phone'] ?: '—') ?></dd>
          <dt class="col-4 text-muted fw-normal">Account</dt>
          <dd class="col-8"><?= $case['reporter_user_id'] ? 'Registered user' : 'Guest submission' ?></dd>
        </dl>
        <p class="text-muted fs-tiny mb-0 mt-2">
          <i class="bi bi-shield-lock me-1"></i>Every view of this panel is audit-logged.
        </p>
      <?php endif; ?>
    </div>

    <!-- evidence -->
    <div class="card p-4 mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-semibold mb-0">Evidence</h2>
        <span class="badge badge-soft bg-secondary"><?= e((string)count($attachments)) ?></span>
      </div>
      <?php if ($attachments === []): ?>
        <p class="text-muted small mb-0">No files attached to this case.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0 small">
          <?php foreach ($attachments as $file): ?>
            <li class="d-flex align-items-center gap-2 py-2 border-bottom">
              <i class="bi <?= e(match ($file['kind']) {
                    'image' => 'bi-file-earmark-image', 'video' => 'bi-file-earmark-play',
                    'audio' => 'bi-file-earmark-music', 'document' => 'bi-file-earmark-pdf',
                    default => 'bi-file-earmark' }) ?> text-primary"></i>
              <a class="flex-grow-1 text-reset" href="<?= e(url('attachments/' . $file['id'])) ?>"
                 target="_blank" rel="noopener">
                <?= e(excerpt($file['original_name'], 24)) ?>
              </a>
              <span class="text-muted fs-tiny"><?= e(bytes((int)$file['size_bytes'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <?php if ($case['resolution_summary']): ?>
      <div class="card p-4 mt-4">
        <h2 class="h6 fw-semibold mb-2">Resolution</h2>
        <p class="small mb-0" style="white-space:pre-line"><?= e($case['resolution_summary']) ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>
