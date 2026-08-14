<?php /** @var array $report */ ?>
<h2 class="h5 fw-semibold mb-1">Acknowledgement of Receipt</h2>
<p class="text-muted small">
  This document confirms that a human rights complaint has been received and registered.
  It is not a determination of the merits of the complaint.
</p>

<table class="table table-sm mt-4">
  <tbody>
    <tr>
      <th scope="row" style="width:36%">Reference number</th>
      <td class="mono fw-semibold"><?= e($report['reference_no']) ?></td>
    </tr>
    <?php if (!empty($report['case_number'])): ?>
      <tr><th scope="row">Case file number</th><td class="mono"><?= e($report['case_number']) ?></td></tr>
    <?php endif; ?>
    <tr><th scope="row">Date received</th><td><?= e(dt($report['created_at'])) ?></td></tr>
    <tr><th scope="row">Category</th><td><?= e($report['category_name']) ?></td></tr>
    <tr><th scope="row">Urgency level</th><td><?= e(ucfirst((string)$report['urgency'])) ?></td></tr>
    <tr><th scope="row">Current status</th><td><?= e(statusLabel((string)$report['status'])) ?></td></tr>
    <tr><th scope="row">Subject of complaint</th><td><?= e($report['title']) ?></td></tr>
    <tr>
      <th scope="row">Incident date</th>
      <td><?= $report['incident_date'] ? e(d($report['incident_date'])) : 'Not specified' ?></td>
    </tr>
    <tr>
      <th scope="row">Location</th>
      <td>
        <?= e($report['location'] ?: 'Not specified') ?>
        <?php if (!empty($report['district_name'])): ?>
          — <?= e($report['district_name']) ?>, <?= e($report['province_name']) ?>
        <?php elseif (!empty($report['province_name'])): ?>
          — <?= e($report['province_name']) ?>
        <?php endif; ?>
      </td>
    </tr>
    <tr>
      <th scope="row">Complainant</th>
      <td><?= (int)$report['is_anonymous'] === 1 ? 'Filed anonymously' : e($report['reporter_name']) ?></td>
    </tr>
    <tr><th scope="row">Number of victims</th><td><?= e((string)$report['victim_count']) ?></td></tr>
  </tbody>
</table>

<h3 class="h6 fw-semibold mt-4">What happens next</h3>
<ol class="small text-muted">
  <li>A supervisor reviews the complaint and confirms it falls within our mandate.</li>
  <li>A human rights officer is assigned and begins the investigation.</li>
  <li>You may be contacted for additional information or an interview.</li>
  <li>A finding is recorded, a remedy recommended where applicable, and the case closed.</li>
</ol>

<div class="border rounded p-3 mt-3 small">
  <strong>Tracking your complaint.</strong>
  Visit <span class="mono"><?= e(url('track')) ?></span> and enter the reference number above.
  <?php if ((int)$report['is_anonymous'] === 1): ?>
    Because this complaint was filed anonymously, the reference number cannot be recovered
    if it is lost.
  <?php endif; ?>
</div>

<p class="text-muted fs-tiny mt-4 mb-0">
  Issued electronically by <?= e(\App\Models\Setting::get('site_name', 'HRRAMS')) ?>
  on <?= e(date('d F Y \a\t H:i')) ?>. No signature is required.
</p>
