<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentPrintController extends Controller
{
    public function __invoke(Request $request, DocumentRequest $document): View
    {
        abort_unless($request->user('employee')?->is_active, 403);
        Gate::forUser($request->user('employee'))->authorize('view', $document);
        abort_unless(in_array($document->status, [RequestStatus::Processing, RequestStatus::AwaitingSignature, RequestStatus::Approved, RequestStatus::Ready, RequestStatus::Released], true), 403);

        return view('filament.requests.print', ['document' => $document->load('resident', 'documentType', 'reservations.resourceItem')]);
    }
}
