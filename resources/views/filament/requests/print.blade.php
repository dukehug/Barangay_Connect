<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->reference }} — {{ $document->documentType->document_type_name }}</title>
    <style>
        body { font: 16px/1.7 Georgia, serif; color: #182a3b; background: #edf1f5; margin: 0; }
        main { box-sizing: border-box; max-width: 210mm; min-height: 270mm; padding: 22mm; background: white; margin: 24px auto; }
        header { text-align: center; border-bottom: 2px solid #182a3b; padding-bottom: 20px; }
        h1 { font-size: 24px; text-align: center; margin: 32px 0; }
        .toolbar { max-width: 210mm; margin: 16px auto; font-family: sans-serif; }
        button { padding: 10px 18px; cursor: pointer; }
        dl div { margin: 12px 0; } dt { font-weight: bold; } dd { margin: 0; white-space: pre-wrap; }
        table { border-collapse: collapse; width: 100%; font-size: 13px; } th, td { border: 1px solid #abb5be; text-align: left; padding: 8px; }
        .signature { margin-top: 65px; text-align: right; } footer { margin-top: 45px; font-size: 12px; }
        @page { size: A4; margin: 0; } @media print { body { background: white; } main { margin: 0; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" id="print-document">Print document</button><p>Check the content before printing. Obtain the required signature, then record the next step in the application. Printing does not mark the document as signed or released.</p></div>
    <main>
        <header>Republic of the Philippines<br><strong>{{ config('barangay.office_name', 'Barangay Office') }}</strong></header>
        <h1>{{ $document->documentType->document_type_name }}</h1>
        <p>Reference: <strong>{{ $document->reference }}</strong><br>Date printed: {{ now()->setTimezone('Asia/Manila')->format('F d, Y') }}</p>
        <p>Issued upon the request of the applicant named below for the stated purpose.</p>
        <dl><div><dt>Applicant</dt><dd>{{ $document->resident->full_name }}</dd></div><div><dt>Address</dt><dd>{{ $document->resident->resident_address }}</dd></div><div><dt>Purpose</dt><dd>{{ $document->purpose }}</dd></div></dl>
        @if($document->workflowCategory() === 'use_closure')
            <h2>Approved arrangement</h2>
            <table><thead><tr><th>Street / equipment</th><th>Quantity</th><th>From</th><th>Until / return by</th></tr></thead><tbody>
                @foreach($document->reservations->where('approved_quantity', '>', 0) as $line)<tr><td>{{ $line->resourceItem->name }}</td><td>{{ $line->approved_quantity }}</td><td>{{ $line->starts_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</td><td>{{ $line->ends_at->setTimezone('Asia/Manila')->format('M d Y H:i') }}</td></tr>@endforeach
            </tbody></table>
            @if($document->review_note)<p>{{ $document->review_note }}</p>@endif
        @endif
        @if($document->official_receipt)<p>Official Receipt: {{ $document->official_receipt }}<br>Amount paid: ₱{{ $document->amount_paid }}</p>@endif
        <div class="signature">________________________________<br>Barangay Officer<br>Signature over printed name</div>
        
        <footer>
            <style>
            footer {
                font-size: 12px;
                text-align: center;
            }
            </style>
            {{ $document->reference }} · {{ config('barangay.office_name', 'Barangay Office') }}
        </footer>
        
    </main>
    <script>document.getElementById('print-document').addEventListener('click', () => window.print());</script>
</body>
</html>
