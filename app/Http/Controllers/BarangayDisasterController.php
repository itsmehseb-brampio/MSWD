<?php

namespace App\Http\Controllers;

use App\Models\BarangayDetail;
use App\Models\DisasterFormatField;
use App\Models\DisasterReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BarangayDisasterController extends Controller
{
    public function apply()
    {
        $barangay = Auth::guard('barangay')->user();
        $details = BarangayDetail::where('barangay_id', $barangay->barangay_id)->first();
        $isOpen = (bool) $barangay->disaster_open;

        $fields = DisasterFormatField::where('field_name', '!=', 'format_no')
            ->orderBy('field_order')
            ->orderBy('field_name')
            ->get();

        $nextNum = (int) DisasterReport::where('barangay_id', $barangay->barangay_id)->max('report_id') + 1;
        $formatNo = $this->formatNumber($barangay->barangay_name, $nextNum);

        return view('barangay.disaster-apply', compact('barangay', 'details', 'isOpen', 'fields', 'formatNo'));
    }

    public function store(Request $request)
    {
        $barangay = Auth::guard('barangay')->user();
        $id = $barangay->barangay_id;

        if (!(bool) $barangay->disaster_open) {
            return redirect()->route('barangay.disaster.apply')
                ->with('error', 'Disaster reporting is currently closed for your barangay.');
        }

        $data = [];
        $fields = DisasterFormatField::where('field_name', '!=', 'format_no')
            ->orderBy('field_order')
            ->orderBy('field_name')
            ->get();
        foreach ($fields as $field) {
            $data[$field->field_name] = trim((string) $request->input($field->field_name, ''));
        }

        $damage = trim((string) ($data['damage_extent'] ?? ''));
        if ($damage === 'Partially Damaged') {
            $damage = 'Partially';
        } elseif ($damage === 'Totally Damaged') {
            $damage = 'Totally';
        }

        $nextNum = (int) DisasterReport::where('barangay_id', $id)->max('report_id') + 1;
        $formatNo = $this->formatNumber($barangay->barangay_name, $nextNum);

        $pics = [];
        for ($i = 1; $i <= 4; $i++) {
            $pics[$i] = $this->uploadFile($request, 'pic' . $i, 'report_' . $id . '_pic' . $i);
        }
        $b2b = $this->uploadFile($request, 'b2b_id1', 'b2b_' . $id);
        if ($b2b === null) {
            $b2b = $this->uploadFile($request, 'b2b_id', 'b2b_' . $id);
        }

        DisasterReport::create([
            'barangay_id' => $id,
            'format_no' => $formatNo,
            'title' => 'Disaster Report ' . $formatNo,
            'disaster_type' => substr((string) ($data['disaster_type'] ?? ''), 0, 100),
            'household_head' => substr((string) ($data['household_head'] ?? ''), 0, 150),
            'family_members' => isset($data['family_members']) && $data['family_members'] !== ''
                ? (int) $data['family_members']
                : 0,
            'full_address' => (string) ($data['full_address'] ?? ''),
            'housing_type' => substr((string) ($data['housing_type'] ?? ''), 0, 100),
            'damage_extent' => (string) $damage,
            'description' => (string) ($data['description'] ?? ''),
            'pic1' => $pics[1],
            'pic2' => $pics[2],
            'pic3' => $pics[3],
            'pic4' => $pics[4],
            'b2b_id' => $b2b,
            'status' => 'pending',
        ]);

        return redirect()->route('barangay.disaster.apply')
            ->with('success', 'Disaster report ' . $formatNo . ' submitted successfully!');
    }

    public function approved(Request $request)
    {
        return $this->listView($request, 'approved', 'Approved Reports', 'Approved Reports', 'fa-check-circle', '#28a745', 'Approved', false);
    }

    public function pending(Request $request)
    {
        return $this->listView($request, 'pending', 'Pending Reports', 'Pending Reports', 'fa-clock', '#fd7e14', 'Pending', false);
    }

    public function declined(Request $request)
    {
        return $this->listView($request, 'declined', 'Declined Reports', 'Declined Reports', 'fa-times-circle', '#dc3545', 'Declined', true);
    }

    public function reedit(Request $request)
    {
        return $this->listView($request, 'reedit', 'For Re-edit', 'Reports For Re-edit', 'fa-redo', '#6f42c1', 'For Re-edit', true, true);
    }

    public function history(Request $request)
    {
        return $this->listView($request, 'history', 'Report History', 'All Reports', 'fa-history', '#0072C6', null, true, true);
    }

    public function edit(Request $request, DisasterReport $report)
    {
        $barangay = Auth::guard('barangay')->user();
        if ((int) $report->barangay_id !== (int) $barangay->barangay_id) {
            return redirect()->route('barangay.disaster.history');
        }
        $details = BarangayDetail::where('barangay_id', $barangay->barangay_id)->first();
        $isReedit = (bool) $request->query('reedit');

        return view('barangay.disaster-edit', compact('barangay', 'report', 'details', 'isReedit'));
    }

    public function update(Request $request, DisasterReport $report)
    {
        $barangay = Auth::guard('barangay')->user();
        if ((int) $report->barangay_id !== (int) $barangay->barangay_id) {
            return redirect()->route('barangay.disaster.history');
        }
        $id = $barangay->barangay_id;

        $damage = trim((string) $request->input('damage_extent', ''));
        if ($damage === 'Partially Damaged') {
            $damage = 'Partially';
        } elseif ($damage === 'Totally Damaged') {
            $damage = 'Totally';
        }

        $payload = [
            'disaster_type' => trim((string) $request->input('disaster_type', $report->disaster_type)),
            'household_head' => trim((string) $request->input('household_head', $report->household_head)),
            'family_members' => (int) ($request->input('family_members') ?: 0),
            'full_address' => trim((string) $request->input('full_address', $report->full_address)),
            'housing_type' => trim((string) $request->input('housing_type', $report->housing_type)),
            'damage_extent' => $damage,
            'description' => trim((string) $request->input('description', $report->description)),
        ];

        for ($i = 1; $i <= 4; $i++) {
            $file = $this->uploadFile($request, 'pic' . $i, 'report_' . $id . '_pic' . $i);
            $payload['pic' . $i] = $file ?? $report->{'pic' . $i};
        }
        $b2b = $this->uploadFile($request, 'b2b_id', 'b2b_' . $id);
        $payload['b2b_id'] = $b2b ?? $report->b2b_id;

        $reedit = $request->query('reedit') || $request->input('reedit');
        if ($reedit) {
            $payload['status'] = 'pending';
            $payload['decline_reason'] = null;
        }

        $report->fill($payload)->save();

        if ($reedit) {
            return redirect()->route('barangay.disaster.reedit')
                ->with('success', 'Report ' . $report->format_no . ' resubmitted for review!');
        }

        return redirect()->route('barangay.disaster.edit', ['report' => $report->report_id])
            ->with('success', 'Report ' . $report->format_no . ' updated successfully!');
    }

    private function listView(
        Request $request,
        string $pageKey,
        string $pageTitle,
        string $cardTitle,
        string $pageIcon,
        string $accent,
        ?string $badgeText,
        bool $showReason,
        bool $isReedit = false
    ) {
        $barangay = Auth::guard('barangay')->user();
        $id = $barangay->barangay_id;

        $query = DisasterReport::where('barangay_id', $id);
        if ($pageKey !== 'history') {
            $query->where('status', $pageKey);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('format_no', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('disaster_type', 'like', $like)
                    ->orWhere('household_head', 'like', $like);
            });
        }

        $reports = $query->orderBy('created_at', 'DESC')->get()->map(function (DisasterReport $r) use ($barangay) {
            $r->setAttribute('date_formatted', $r->created_at ? $r->created_at->format('M d, Y') : '—');
            $r->setAttribute('time_formatted', $r->created_at ? $r->created_at->format('g:i A') : '');
            $r->setAttribute('detail_dump', [
                'format_no' => $r->format_no,
                'captain_name' => $barangay->barangay_name,
                'secretary_name' => 'Barangay Secretariat',
                'decline_reason' => $r->decline_reason,
                'description' => $r->description,
                'household_head' => $r->household_head,
                'disaster_type' => $r->disaster_type,
                'family_members' => $r->family_members,
                'full_address' => $r->full_address,
                'housing_type' => $r->housing_type,
                'damage_extent' => $r->damage_extent,
            ]);
            return $r;
        });

        $statusNav = [
            ['key' => 'approved', 'label' => 'Approved', 'color' => '#28a745', 'active' => $pageKey === 'approved'],
            ['key' => 'pending', 'label' => 'Pending', 'color' => '#fd7e14', 'active' => $pageKey === 'pending'],
            ['key' => 'declined', 'label' => 'Declined', 'color' => '#dc3545', 'active' => $pageKey === 'declined'],
            ['key' => 'reedit', 'label' => 'For Re-edit', 'color' => '#6f42c1', 'active' => $pageKey === 'reedit'],
            ['key' => 'history', 'label' => 'History', 'color' => '#0072C6', 'active' => $pageKey === 'history'],
        ];

        $details = BarangayDetail::where('barangay_id', $id)->first();

        return view('barangay.disaster-list', compact(
            'barangay', 'pageKey', 'pageTitle', 'cardTitle', 'pageIcon', 'accent',
            'badgeText', 'showReason', 'isReedit', 'reports', 'search', 'statusNav', 'details'
        ));
    }

    private function formatNumber(string $barangayName, int $nextNum): string
    {
        $letters = '';
        foreach (preg_split('/\s+/', trim($barangayName)) as $word) {
            if ($word === '') {
                continue;
            }
            $letters .= strtoupper($word[0]);
            if (strlen($letters) === 2) {
                break;
            }
        }
        return 'D' . $letters . '-' . str_pad((string) $nextNum, 3, '0', STR_PAD_LEFT);
    }

    private function uploadFile(Request $request, string $field, string $prefix): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }
        $file = $request->file($field);
        if (!$file->isValid()) {
            return null;
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }
        $directory = public_path('uploads');
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        $fname = $prefix . '_' . time() . '.' . $ext;
        $file->move($directory, $fname);

        return $fname;
    }
}