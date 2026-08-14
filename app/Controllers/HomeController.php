<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\EventItem;
use App\Models\Lookup;
use App\Models\Report;
use App\Models\ResourceItem;
use App\Models\Setting;

final class HomeController extends Controller
{
    public function index(): void
    {
        $reports = new Report();
        $this->view('public/home', [
            'title'      => 'Home',
            'stats'      => $reports->publicStats(),
            'campaigns'  => (new Campaign())->published('campaign', 3),
            'news'       => (new Campaign())->published('news', 4),
            'resources'  => (new ResourceItem())->published(4),
            'events'     => (new EventItem())->upcoming(3),
            'categories' => (new Category())->active(),
        ]);
    }

    public function about(): void
    {
        $this->view('public/about', ['title' => 'About the Commission']);
    }

    public function faqs(): void
    {
        $this->view('public/faqs', ['title' => 'Frequently Asked Questions']);
    }

    public function contact(): void
    {
        $this->view('public/contact', [
            'title'    => 'Contact Us',
            'settings' => Setting::all(),
        ]);
    }

    public function submitContact(): void
    {
        $data = $this->validate([
            'name'    => 'required|min:2|max:120',
            'email'   => 'required|email|max:190',
            'subject' => 'required|min:3|max:150',
            'message' => 'required|min:10|max:2000',
        ], [], 'contact');

        $to   = (string)Setting::get('contact_email', 'info@hrrams.test');
        $body = '<p><strong>From:</strong> ' . e($data['name']) . ' &lt;' . e($data['email']) . '&gt;</p>'
              . '<p>' . nl2br(e($data['message'])) . '</p>';

        Mailer::send($to, '[Contact] ' . $data['subject'], $body);
        Flash::success('Thank you for reaching out. We will respond within two working days.');
        $this->redirect('contact');
    }

    public function statistics(): void
    {
        $reports = new Report();
        $this->view('public/statistics', [
            'title'      => 'Human Rights Statistics',
            'stats'      => $reports->publicStats(),
            'byCategory' => $reports->byCategory(),
            'byProvince' => $reports->byProvince(),
            'byStatus'   => $reports->byStatus(),
            'trend'      => $reports->monthlyTrend(12),
        ]);
    }

    /** Feeds the public charts without exposing any personal data. */
    public function statisticsJson(): void
    {
        $reports = new Report();
        $this->json([
            'byCategory' => $reports->byCategory(),
            'byProvince' => $reports->byProvince(),
            'byStatus'   => $reports->byStatus(),
            'trend'      => $reports->monthlyTrend(12),
        ]);
    }

    /** Cascading province -> district select on the reporting wizard. */
    public function districtsJson(string $province): void
    {
        if (!ctype_digit($province)) {
            $this->json(['error' => 'Invalid province.'], 400);
        }
        $this->json(Lookup::districts((int)$province));
    }
}
