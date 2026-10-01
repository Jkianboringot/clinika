<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color:#222; max-width:600px; margin:auto;">
    <h2>Hello {{ $patient->full_name }},</h2>
    <p>Your treatment is complete. Here's a summary:</p>

    <h3>Treatment Summary</h3>
    @if (filled($treatment->summary))
        <p>{!! nl2br(e($treatment->summary)) !!}</p>
    @else
        <p>No summary provided.</p>
    @endif

    @if ($treatment->duration_minutes)
        <p><strong>Duration:</strong> {{ $treatment->duration_minutes }} minutes</p>
    @endif

    <h3>Prescription</h3>
    @if ($prescriptions->isEmpty())
        <p>No medication prescribed.</p>
    @else
        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
            <thead>
                <tr style="background:#f3f4f6; text-align:left;">
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prescriptions as $p)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td>{{ $p['name'] }}</td>
                        <td>{{ $p['dosage'] }}</td>
                        <td>{{ $p['frequency'] }}</td>
                        <td>{{ $p['duration_days'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h3>Follow-up Schedule</h3>
    <p>{{ $treatment->follow_up_schedule ? \Carbon\Carbon::parse($treatment->follow_up_schedule)->format('F d, Y') : 'None scheduled' }}</p>

    <p style="margin-top:24px;">Thank you, and take care!</p>
</body>
</html>