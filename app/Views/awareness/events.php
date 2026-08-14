<?php /** @var array $events */ ?>
<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="eyebrow">Get involved</span>
      <h1 class="section-title fw-semibold mt-3 mb-2">Community events</h1>
      <p class="text-muted mx-auto" style="max-width:42rem">
        Legal aid clinics, training sessions and outreach meetings. All are free to attend.
      </p>
    </div>

    <?php if ($events === []): ?>
      <div class="card">
        <div class="empty-state"><i class="bi bi-calendar-x"></i>No upcoming events are scheduled.</div>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($events as $event): ?>
          <div class="col-md-6">
            <article class="card h-100 p-4">
              <div class="d-flex gap-3">
                <div class="text-center flex-shrink-0">
                  <div class="stat-icon tint-primary mx-auto" style="width:56px;height:56px">
                    <div>
                      <div class="fw-semibold" style="font-size:1.05rem;line-height:1">
                        <?= e(date('d', strtotime((string)$event['starts_at']))) ?>
                      </div>
                      <div style="font-size:.62rem;text-transform:uppercase">
                        <?= e(date('M', strtotime((string)$event['starts_at']))) ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div>
                  <h2 class="h6 fw-semibold mb-1"><?= e($event['title']) ?></h2>
                  <p class="text-muted fs-tiny mb-2">
                    <i class="bi bi-clock me-1"></i>
                    <?= e(date('H:i', strtotime((string)$event['starts_at']))) ?>
                    <?php if ($event['ends_at']): ?>
                      – <?= e(date('H:i', strtotime((string)$event['ends_at']))) ?>
                    <?php endif; ?>
                    <?php if ($event['venue']): ?>
                      · <i class="bi bi-geo-alt me-1"></i><?= e($event['venue']) ?>
                    <?php endif; ?>
                    <?php if ($event['province_name']): ?>
                      , <?= e($event['province_name']) ?>
                    <?php endif; ?>
                  </p>
                  <?php if ($event['description']): ?>
                    <p class="text-muted small mb-0"><?= e(excerpt($event['description'], 150)) ?></p>
                  <?php endif; ?>
                  <?php if ($event['capacity']): ?>
                    <p class="fs-tiny text-muted mb-0 mt-2">
                      <i class="bi bi-people me-1"></i>Capacity <?= e((string)$event['capacity']) ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
