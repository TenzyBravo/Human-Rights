<?php /** @var array $errors @var array $old */ use App\Models\Setting; ?>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <span class="eyebrow">Get in touch</span>
        <h1 class="section-title fw-semibold mt-3 mb-3">We would like to hear from you</h1>
        <p class="text-muted">
          For urgent matters, use the hotline rather than this form. To file a formal
          complaint, please use the reporting form so it enters the case system properly.
        </p>

        <div class="card p-3 mb-3">
          <div class="d-flex gap-3 align-items-start">
            <div class="stat-icon tint-danger"><i class="bi bi-telephone-inbound"></i></div>
            <div>
              <div class="fw-semibold small">Emergency hotline</div>
              <div class="text-muted small"><?= e(Setting::get('hotline', '933')) ?> — available 24 hours</div>
            </div>
          </div>
        </div>
        <div class="card p-3 mb-3">
          <div class="d-flex gap-3 align-items-start">
            <div class="stat-icon tint-primary"><i class="bi bi-envelope"></i></div>
            <div>
              <div class="fw-semibold small">Email</div>
              <div class="text-muted small"><?= e(Setting::get('contact_email', '')) ?></div>
            </div>
          </div>
        </div>
        <div class="card p-3">
          <div class="d-flex gap-3 align-items-start">
            <div class="stat-icon tint-secondary"><i class="bi bi-telephone"></i></div>
            <div>
              <div class="fw-semibold small">Office line</div>
              <div class="text-muted small"><?= e(Setting::get('contact_phone', '')) ?></div>
              <div class="text-muted fs-tiny">Monday to Friday, 08:00 – 17:00</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card p-4 p-lg-4">
          <h2 class="h5 fw-semibold mb-3">Send us a message</h2>
          <form method="post" action="<?= e(url('contact')) ?>" novalidate>
            <?= csrf() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label required" for="name">Your name</label>
                <input type="text" id="name" name="name" required maxlength="120"
                       class="form-control<?= invalid($errors, 'name') ?>" value="<?= old($old, 'name') ?>">
                <?= errorFor($errors, 'name') ?>
              </div>
              <div class="col-md-6">
                <label class="form-label required" for="email">Email address</label>
                <input type="email" id="email" name="email" required maxlength="190"
                       class="form-control<?= invalid($errors, 'email') ?>" value="<?= old($old, 'email') ?>">
                <?= errorFor($errors, 'email') ?>
              </div>
              <div class="col-12">
                <label class="form-label required" for="subject">Subject</label>
                <input type="text" id="subject" name="subject" required maxlength="150"
                       class="form-control<?= invalid($errors, 'subject') ?>" value="<?= old($old, 'subject') ?>">
                <?= errorFor($errors, 'subject') ?>
              </div>
              <div class="col-12">
                <label class="form-label required" for="message">Message</label>
                <textarea id="message" name="message" rows="6" required maxlength="2000"
                          class="form-control<?= invalid($errors, 'message') ?>"><?= old($old, 'message') ?></textarea>
                <div class="form-text">Please do not include sensitive personal details in this form.</div>
                <?= errorFor($errors, 'message') ?>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-send me-2"></i>Send message
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
