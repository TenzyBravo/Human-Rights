<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-7">
        <span class="eyebrow">About us</span>
        <h1 class="section-title fw-semibold mt-3 mb-3">Protecting rights, promoting dignity</h1>
        <p class="text-muted">
          This platform exists so that any person — regardless of where they live, what they earn
          or who they know — can raise a human rights concern and have it handled properly.
          It combines four things in one place: public education, a secure complaints channel,
          a disciplined case-management process, and open reporting on the outcomes.
        </p>

        <h2 class="h5 fw-semibold mt-4 mb-2">What we do</h2>
        <ul class="text-muted">
          <li>Receive and investigate complaints of human rights violations.</li>
          <li>Assign every complaint to a named officer under supervisory oversight.</li>
          <li>Mediate, recommend remedies and refer matters to the appropriate authority.</li>
          <li>Run awareness campaigns, training and community legal aid clinics.</li>
          <li>Publish statistics so the public can see what is being reported and resolved.</li>
        </ul>

        <h2 class="h5 fw-semibold mt-4 mb-2">Our commitments to you</h2>
        <div class="row g-3">
          <?php
          $commitments = [
              ['bi-eye-slash', 'Confidentiality', 'Your identity is never shared outside the case team, and anonymous reports are fully supported.'],
              ['bi-clock-history', 'Timeliness', 'Every case carries a service deadline. Critical matters are actioned within three days.'],
              ['bi-scale', 'Impartiality', 'Officers act independently of the parties involved and record every decision.'],
              ['bi-journal-check', 'Accountability', 'Every action on your case is logged and auditable.'],
          ];
          foreach ($commitments as [$icon, $heading, $text]): ?>
            <div class="col-sm-6">
              <div class="card h-100 p-3">
                <div class="stat-icon tint-primary mb-2"><i class="bi <?= e($icon) ?>"></i></div>
                <div class="fw-semibold small"><?= e($heading) ?></div>
                <p class="text-muted small mb-0"><?= e($text) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card p-4">
          <h2 class="h6 fw-semibold mb-3">The rights we uphold</h2>
          <p class="text-muted small">
            Our mandate draws on the Universal Declaration of Human Rights and the Bill of
            Rights in the national constitution. These include the right to life, dignity,
            equality before the law, freedom from torture, freedom of expression and assembly,
            access to education and health, fair labour practices, and the special protections
            owed to children and persons with disabilities.
          </p>
          <a href="<?= e(url('resources')) ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-journal-text me-1"></i>Read the source documents
          </a>
        </div>

        <div class="card p-4 mt-3">
          <h2 class="h6 fw-semibold mb-2">Need to raise something now?</h2>
          <p class="text-muted small mb-3">You do not need an account, and you do not need to give your name.</p>
          <a href="<?= e(url('report')) ?>" class="btn btn-primary w-100">
            <i class="bi bi-megaphone me-2"></i>Report a violation
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
