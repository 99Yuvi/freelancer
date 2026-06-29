<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #1a1a2e; padding: 40px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
  .brand { font-size: 22px; font-weight: 700; color: #334155; letter-spacing: -0.5px; }
  .invoice-meta { text-align: right; color: #64748b; }
  .invoice-meta h2 { font-size: 20px; color: #1e293b; }
  .parties { display: flex; justify-content: space-between; margin-bottom: 32px; }
  .party h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 6px; }
  .party p { line-height: 1.6; color: #334155; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; }
  td { padding: 12px; border-bottom: 1px solid #f1f5f9; color: #334155; }
  .totals { width: 280px; margin-left: auto; }
  .totals td { border: none; padding: 5px 12px; }
  .totals .total-row { font-weight: 700; font-size: 15px; color: #1e293b; border-top: 2px solid #334155; }
  .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 11px; text-align: center; }
  .badge { display: inline-block; background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
</style>
</head>
<body>
  <div class="header">
    <div>
      <div class="brand">Operalyn</div>
      <div style="color:#64748b;font-size:12px;margin-top:4px;">Operalyn Freelance Network Services Pvt. Ltd.</div>
      <div style="color:#64748b;font-size:12px;">operalyn.com</div>
    </div>
    <div class="invoice-meta">
      <span class="badge">PAID</span>
      <h2 style="margin-top:8px;">{{ $invoiceNumber }}</h2>
      <p>Issued: {{ $issuedDate }}</p>
    </div>
  </div>

  <div class="parties">
    <div class="party">
      <h4>Billed to (Client)</h4>
      <p><strong>{{ $payment->client->name }}</strong></p>
      @if($payment->client->clientProfile?->company_name)
        <p>{{ $payment->client->clientProfile->company_name }}</p>
      @endif
    </div>
    <div class="party" style="text-align:right">
      <h4>Freelancer</h4>
      <p><strong>{{ $payment->freelancer->name }}</strong></p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th>Project</th>
        <th style="text-align:right">Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $payment->milestone->title }}</td>
        <td style="color:#64748b">{{ $payment->contract->project->title }}</td>
        <td style="text-align:right">₹{{ number_format($payment->amount, 2) }}</td>
      </tr>
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <td style="color:#64748b">Gross amount</td>
      <td style="text-align:right">₹{{ number_format($payment->amount, 2) }}</td>
    </tr>
    <tr>
      <td style="color:#64748b">Platform fee ({{ $payment->commission_rate }}%)</td>
      <td style="text-align:right; color:#dc2626">−₹{{ number_format($payment->commission_amount, 2) }}</td>
    </tr>
    <tr class="total-row">
      <td>Freelancer net earnings</td>
      <td style="text-align:right">₹{{ number_format($payment->net_amount, 2) }}</td>
    </tr>
  </table>

  <div class="footer">
    <p>Payment processed via Razorpay · Order ID: {{ $payment->razorpay_order_id }}</p>
    <p style="margin-top:4px;">This is a computer-generated invoice. No signature required.</p>
    <p style="margin-top:4px;">© {{ now()->year }} Operalyn Freelance Network Services Pvt. Ltd.</p>
  </div>
</body>
</html>
