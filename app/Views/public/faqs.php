<?php
$faqs = [
    ['Do I have to give my name?',
     'No. Select "Report anonymously" on the first step of the form. You will still receive a reference number so you can track progress, but no identifying details are stored. Note that we cannot come back to you for clarification on an anonymous report, which sometimes limits how far an investigation can go.'],
    ['How long does an investigation take?',
     'It depends on the complexity and the urgency level. Critical reports carry a three-day first-action deadline, high urgency seven days, and standard matters thirty days. The average resolution time across all concluded cases is published on the statistics page.'],
    ['What evidence should I attach?',
     'Anything that helps establish what happened: photographs, video or audio recordings, medical or police documents, letters, contracts, or screenshots. You may upload images, video, audio and PDF or Word documents, up to 20 MB per file and ten files per report.'],
    ['Is my information safe?',
     'Yes. The site runs over an encrypted connection, evidence files are stored outside the public web directory and are only released to the officer handling your case, sensitive identifiers are encrypted in the database, and every access to your file is written to an audit log.'],
    ['Can I add information after submitting?',
     'Yes. If you registered an account, open the report from "My reports" and the case team can be reached through it. If an officer needs more from you, you will receive an email and the case will show as "Awaiting information".'],
    ['What happens if my report is rejected?',
     'A report is only rejected when it falls outside our mandate — for example a purely commercial dispute with no rights dimension. You will be told why, and wherever possible referred to the body that can help.'],
    ['Can I report on behalf of someone else?',
     'Yes. Describe your relationship to the affected person in the "Persons involved" field. If the person is a child or is at immediate risk, mark the urgency as critical and also call the hotline.'],
    ['Does it cost anything?',
     'No. Filing a report, tracking it, and every service on this platform is free.'],
];
?>
<section class="section">
  <div class="container" style="max-width:52rem">
    <div class="text-center mb-4">
      <span class="eyebrow">Help</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Frequently asked questions</h1>
      <p class="text-muted">If your question is not answered here, please get in touch.</p>
    </div>

    <div class="accordion" id="faqAccordion">
      <?php foreach ($faqs as $i => [$question, $answer]): ?>
        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button"
                    data-bs-toggle="collapse" data-bs-target="#faq<?= e((string)$i) ?>"
                    aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq<?= e((string)$i) ?>">
              <?= e($question) ?>
            </button>
          </h2>
          <div id="faq<?= e((string)$i) ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>"
               data-bs-parent="#faqAccordion">
            <div class="accordion-body text-muted small"><?= e($answer) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card p-4 mt-4 text-center">
      <p class="mb-3 small">Still stuck? Our team responds within two working days.</p>
      <div class="d-flex gap-2 justify-content-center">
        <a href="<?= e(url('contact')) ?>" class="btn btn-primary btn-sm">Contact us</a>
        <a href="<?= e(url('report')) ?>" class="btn btn-outline-secondary btn-sm">Start a report</a>
      </div>
    </div>
  </div>
</section>
