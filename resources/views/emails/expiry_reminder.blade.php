<!DOCTYPE html>
<html>
<head>
    <title>Document Expiry Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #d9534f;">Document Expiry Reminder</h2>
        <p>Hello Admin,</p>
        <p>This is an automated reminder that a document is expiring soon.</p>
        
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Employee</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $doc->employee->full_name }} ({{ $doc->employee->employee_id }})</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Document Type</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $doc->type }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Document No.</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $doc->document_number }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Expiry Date</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $doc->expiry_date }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold; background-color: #f9f9f9;">Days Left</td>
                <td style="padding: 8px; border: 1px solid #ddd; color: #d9534f; font-weight: bold;">{{ $daysLeft }} Days</td>
            </tr>
        </table>
        
        <p style="margin-top: 20px;">Please take the necessary actions to renew this document.</p>
        <p>Thank you,<br>HR Management System</p>
    </div>
</body>
</html>
