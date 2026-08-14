<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Uploader;
use App\Models\Campaign;
use App\Models\EventItem;
use App\Models\Lookup;
use App\Models\ResourceItem;

final class AwarenessController extends Controller
{
    // ------------------------------------------------------------------ public
    public function campaigns(): void
    {
        $this->view('awareness/list', [
            'title'   => 'Awareness campaigns',
            'heading' => 'Awareness campaigns',
            'lead'    => 'Community programmes promoting knowledge of human rights across all ten provinces.',
            'items'   => (new Campaign())->published('campaign'),
        ]);
    }

    public function news(): void
    {
        $this->view('awareness/list', [
            'title'   => 'News',
            'heading' => 'News & announcements',
            'lead'    => 'The latest updates from the Commission.',
            'items'   => (new Campaign())->published('news'),
        ]);
    }

    public function stories(): void
    {
        $this->view('awareness/list', [
            'title'   => 'Success stories',
            'heading' => 'Success stories',
            'lead'    => 'Outcomes achieved with and for the communities we serve.',
            'items'   => (new Campaign())->published('success_story'),
        ]);
    }

    public function show(string $slug): void
    {
        $campaigns = new Campaign();
        $item      = $campaigns->findBySlug($slug);
        if ($item === null) {
            $this->fail(404, 'That page could not be found.', 'public');
        }
        $campaigns->registerView((int)$item['id']);
        $this->view('awareness/show', [
            'title'   => $item['title'],
            'item'    => $item,
            'related' => array_slice(array_filter(
                $campaigns->published((string)$item['type'], 4),
                static fn($r) => (int)$r['id'] !== (int)$item['id']
            ), 0, 3),
        ]);
    }

    public function resources(): void
    {
        $this->view('awareness/resources', [
            'title'     => 'Educational resources',
            'resources' => (new ResourceItem())->published(),
        ]);
    }

    public function showResource(string $slug): void
    {
        $resources = new ResourceItem();
        $item      = $resources->findBySlug($slug);
        if ($item === null || (int)$item['is_published'] !== 1) {
            $this->fail(404, 'That resource could not be found.', 'public');
        }
        $resources->registerDownload((int)$item['id']);
        $this->view('awareness/resource-show', ['title' => $item['title'], 'item' => $item]);
    }

    public function events(): void
    {
        $this->view('awareness/events', [
            'title'  => 'Community events',
            'events' => (new EventItem())->upcoming(30),
        ]);
    }

    // ------------------------------------------------------------- management
    public function manage(): void
    {
        $campaigns = new Campaign();
        $filters   = Request::only(['q', 'type', 'status']);
        $page      = $this->paginate($campaigns->searchCount($filters));

        $this->view('awareness/manage', [
            'title'   => 'Manage awareness content',
            'rows'    => $campaigns->search($filters, $page['perPage'], $page['offset']),
            'page'    => $page,
            'filters' => $filters,
        ], 'app');
    }

    public function form(?string $id = null): void
    {
        $item = null;
        if ($id !== null && ctype_digit($id)) {
            $item = (new Campaign())->find((int)$id);
            if ($item === null) {
                Flash::error('That item no longer exists.');
                $this->redirect('awareness/manage');
            }
        }
        $this->view('awareness/form', [
            'title' => $item === null ? 'New content' : 'Edit content',
            'item'  => $item,
            'types' => Campaign::TYPES,
        ], 'app');
    }

    public function save(): void
    {
        $data = $this->validate([
            'id'        => 'nullable|int',
            'title'     => 'required|min:5|max:200',
            'type'      => 'required|in:' . implode(',', Campaign::TYPES),
            'summary'   => 'nullable|max:500',
            'body'      => 'nullable|max:200000',
            'status'    => 'required|in:draft,published,archived',
            'starts_on' => 'nullable|date',
            'ends_on'   => 'nullable|date',
        ], [], 'awareness/create');

        $campaigns = new Campaign();
        $id        = !empty($data['id']) ? (int)$data['id'] : null;

        $payload = [
            'title'     => $data['title'],
            'slug'      => $campaigns->uniqueSlug((string)$data['title'], $id),
            'type'      => $data['type'],
            'summary'   => $data['summary'] ?: null,
            // Rich text is stored raw and sanitised on output: safeHtml() keeps a
            // small tag allow-list and strips every attribute before rendering.
            'body'      => $data['body'] ?: null,
            'status'    => $data['status'],
            'starts_on' => $data['starts_on'] ?: null,
            'ends_on'   => $data['ends_on'] ?: null,
        ];
        if ($data['status'] === 'published') {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        if ($id === null) {
            $newId = $campaigns->create($payload);
            Audit::log('awareness.created', 'campaign', $newId, $data['title']);
            Flash::success('Content created.');
            $id = $newId;
        } else {
            $campaigns->updateById($id, $payload);
            Audit::log('awareness.updated', 'campaign', $id, $data['title']);
            Flash::success('Content updated.');
        }

        $upload = Uploader::handle('cover', 'campaign', $id);
        if ($upload['stored'] !== []) {
            $campaigns->updateById($id, [
                'cover_path' => 'attachment:' . $upload['stored'][0]['id'],
            ]);
        }
        foreach ($upload['errors'] as $message) {
            Flash::warning($message);
        }

        $this->redirect('awareness/manage');
    }

    public function destroy(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('awareness/manage');
        }
        (new Campaign())->softDelete((int)$id);
        Audit::log('awareness.deleted', 'campaign', (int)$id, 'Content removed');
        Flash::success('Content deleted.');
        $this->redirect('awareness/manage');
    }

    // -------------------------------------------------------------- resources
    public function manageResources(): void
    {
        $this->view('awareness/manage-resources', [
            'title'     => 'Educational resources',
            'resources' => (new ResourceItem())->allRows('created_at DESC'),
            'types'     => ResourceItem::TYPES,
        ], 'app');
    }

    public function saveResource(): void
    {
        $data = $this->validate([
            'id'            => 'nullable|int',
            'title'         => 'required|min:5|max:200',
            'resource_type' => 'required|in:' . implode(',', ResourceItem::TYPES),
            'description'   => 'nullable|max:5000',
            'external_url'  => 'nullable|url|max:500',
        ], [], 'awareness/resources');

        $resources = new ResourceItem();
        $id        = !empty($data['id']) ? (int)$data['id'] : null;
        $payload   = [
            'title'         => $data['title'],
            'slug'          => $resources->uniqueSlug((string)$data['title'], $id),
            'resource_type' => $data['resource_type'],
            'description'   => $data['description'] ?: null,
            'external_url'  => $data['external_url'] ?: null,
            'is_published'  => Request::bool('is_published') ? 1 : 0,
        ];

        if ($id === null) {
            $id = $resources->create($payload);
            Audit::log('resource.created', 'resource', $id, $data['title']);
            Flash::success('Resource created.');
        } else {
            $resources->updateById($id, $payload);
            Audit::log('resource.updated', 'resource', $id, $data['title']);
            Flash::success('Resource updated.');
        }

        $upload = Uploader::handle('file', 'resource', $id);
        if ($upload['stored'] !== []) {
            $resources->updateById($id, ['file_path' => 'attachment:' . $upload['stored'][0]['id']]);
        }
        foreach ($upload['errors'] as $message) {
            Flash::warning($message);
        }
        $this->redirect('awareness/resources');
    }

    public function destroyResource(string $id): void
    {
        if (ctype_digit($id)) {
            (new ResourceItem())->softDelete((int)$id);
            Audit::log('resource.deleted', 'resource', (int)$id, 'Resource removed');
            Flash::success('Resource deleted.');
        }
        $this->redirect('awareness/resources');
    }

    // ----------------------------------------------------------------- events
    public function manageEvents(): void
    {
        $this->view('awareness/manage-events', [
            'title'     => 'Community events',
            'events'    => (new EventItem())->allWithProvince(),
            'provinces' => Lookup::provinces(),
        ], 'app');
    }

    public function saveEvent(): void
    {
        $data = $this->validate([
            'id'          => 'nullable|int',
            'title'       => 'required|min:5|max:200',
            'description' => 'nullable|max:5000',
            'venue'       => 'nullable|max:200',
            'province_id' => 'nullable|int',
            'starts_at'   => 'required|max:20',
            'ends_at'     => 'nullable|max:20',
            'capacity'    => 'nullable|int|between:0,100000',
            'status'      => 'required|in:draft,published,cancelled,completed',
        ], [], 'awareness/events');

        $start = strtotime((string)$data['starts_at']);
        if ($start === false) {
            Flash::error('Please provide a valid start date and time.');
            $this->redirect('awareness/events');
        }
        $end = !empty($data['ends_at']) ? strtotime((string)$data['ends_at']) : false;

        $payload = [
            'title'       => $data['title'],
            'description' => $data['description'] ?: null,
            'venue'       => $data['venue'] ?: null,
            'province_id' => $data['province_id'] ? (int)$data['province_id'] : null,
            'starts_at'   => date('Y-m-d H:i:s', $start),
            'ends_at'     => $end === false ? null : date('Y-m-d H:i:s', $end),
            'capacity'    => $data['capacity'] !== '' ? (int)$data['capacity'] : null,
            'status'      => $data['status'],
        ];

        $events = new EventItem();
        if (empty($data['id'])) {
            $id = $events->create($payload);
            Audit::log('event.created', 'event', $id, $data['title']);
            Flash::success('Event created.');
        } else {
            $events->updateById((int)$data['id'], $payload);
            Audit::log('event.updated', 'event', (int)$data['id'], $data['title']);
            Flash::success('Event updated.');
        }
        $this->redirect('awareness/events');
    }

    public function analytics(): void
    {
        $campaigns = new Campaign();
        $this->view('awareness/analytics', [
            'title'     => 'Campaign analytics',
            'summary'   => $campaigns->analytics(),
            'top'       => $campaigns->topViewed(8),
            'resources' => (new ResourceItem())->allRows('downloads DESC'),
        ], 'app');
    }
}
