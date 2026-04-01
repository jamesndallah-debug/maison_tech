<?php
include 'header.php';
include 'dp.php';

$sale_id = (int)($_GET['id'] ?? 0);
$autoprint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

// Fetch sale details
$stmt = $conn->prepare("SELECT s.*, u.username FROM sales s JOIN employees u ON s.user_id = u.id WHERE s.id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sale) {
    echo "<div class='alert alert-danger'>Sale not found.</div>";
    include 'footer.php';
    exit;
}

// Fetch sale items
$stmt = $conn->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>

<div class="receipt-container">
    <div class="receipt-card" id="receipt">
        <div class="receipt-header">
            <div class="brand">
                <div class="brand-name">Maison Tech</div>
                <div class="brand-sub">POS Receipt</div>
            </div>
            <div class="receipt-info">
                <p><strong>Receipt #<?php echo $sale['id']; ?></strong></p>
                <p><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></p>
            </div>
        </div>

        <div class="receipt-details">
            <p><strong>Served by:</strong> <?php echo htmlspecialchars($sale['username']); ?></p>
            <p class="muted"><strong>Payment:</strong> <?php 
                echo htmlspecialchars($sale['payment_method']); 
                if (!empty($sale['payment_provider'])) {
                    echo " (" . htmlspecialchars($sale['payment_provider']) . ")";
                }
            ?></p>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-right">TSh <?php echo number_format($item['price_per_unit'], 0); ?></td>
                    <td class="text-right">TSh <?php echo number_format($item['price_per_unit'] * $item['quantity'], 0); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">Grand Total</td>
                    <td class="text-right">TSh <?php echo number_format($sale['total_amount'], 0); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="receipt-footer">
            <p class="thanks">Thank you for shopping with us!</p>
            <p class="muted">Keep this receipt for your records.</p>
            
            <div class="whatsapp-share no-print">
                <p>Share to WhatsApp:</p>
                <div class="share-form">
                    <input type="text" id="phone" placeholder="e.g. 255712345678" class="form-control">
                    <button onclick="shareWhatsApp()" class="btn btn-success"><i class="fab fa-whatsapp"></i> Share</button>
                </div>
            </div>

            <div class="actions no-print">
                <button onclick="printReceipt('a4')" class="btn btn-primary"><i class="fas fa-print"></i> Print (A4)</button>
                <button onclick="printReceipt('thermal')" class="btn btn-secondary"><i class="fas fa-receipt"></i> Print (80mm)</button>
                <button onclick="downloadReceipt()" class="btn btn-info"><i class="fas fa-download"></i> Download Image</button>
                <a href="sales.php" class="btn btn-secondary">New Sale</a>
            </div>
        </div>
    </div>
</div>

<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
function printReceipt(mode) {
    document.body.classList.remove('print-a4', 'print-thermal');
    if (mode === 'thermal') document.body.classList.add('print-thermal');
    else document.body.classList.add('print-a4');
    window.print();
}

function shareWhatsApp() {
    const phone = document.getElementById('phone').value;
    if (!phone) {
        alert("Please enter a phone number");
        return;
    }
    
    // Get receipt text for WhatsApp
    let text = "*Maison Tech Receipt*\n";
    text += "Receipt #<?php echo $sale['id']; ?>\n";
    text += "Date: <?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?>\n";
    text += "------------------\n";
    
    <?php 
    // Reset internal pointer to fetch items again
    $items->data_seek(0);
    while($item = $items->fetch_assoc()): ?>
    text += "<?php echo $item['name']; ?> x <?php echo $item['quantity']; ?>: TSh <?php echo number_format($item['price_per_unit'] * $item['quantity'], 0); ?>\n";
    <?php endwhile; ?>
    
    text += "------------------\n";
    text += "*Total: TSh <?php echo number_format($sale['total_amount'], 0); ?>*\n";
    text += "Thank you for shopping!";

    const url = "https://api.whatsapp.com/send?phone=" + phone + "&text=" + encodeURIComponent(text);
    window.open(url, '_blank');
}

function downloadReceipt() {
    const receipt = document.getElementById('receipt');
    const actions = receipt.querySelector('.actions');
    const whatsapp = receipt.querySelector('.whatsapp-share');
    
    // Temporarily hide buttons for capture
    actions.style.display = 'none';
    whatsapp.style.display = 'none';

    html2canvas(receipt, {
        scale: 2, // Higher quality
        backgroundColor: '#ffffff'
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'receipt-<?php echo $sale['id']; ?>.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        
        // Show buttons again
        actions.style.display = 'flex';
        whatsapp.style.display = 'block';
    });
}

<?php if ($autoprint): ?>
document.addEventListener('DOMContentLoaded', function () {
    printReceipt('thermal');
});
<?php endif; ?>
</script>

<style>
    .receipt-container { display: flex; justify-content: center; padding: 40px 0; }
    .receipt-card { background: white; width: 420px; padding: 36px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
    .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; }
    .brand-name { font-size: 20px; font-weight: 900; color: #0f172a; line-height: 1.1; }
    .brand-sub { font-size: 12px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-top: 6px; }
    .receipt-info { text-align: right; color: #64748b; font-size: 13px; }
    .receipt-info p { margin: 0; }
    .receipt-details { margin-bottom: 20px; font-size: 14px; color: #475569; }
    .muted { color: #64748b; }
    
    .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .receipt-table th { font-size: 11px; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding: 8px 0; }
    .receipt-table td { padding: 12px 0; font-size: 14px; color: #1e293b; }
    .text-right { text-align: right; }
    .total-row td { font-weight: 700; font-size: 18px; border-top: 2px solid #f1f5f9; padding-top: 16px; }
    
    .receipt-footer { text-align: center; color: #94a3b8; font-size: 13px; margin-top: 34px; }
    .thanks { color: #334155; font-weight: 700; }
    .actions { margin-top: 24px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; }
    .btn-success { background: #25d366; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .btn-info { background: #0ea5e9; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; }
    
    .whatsapp-share { margin-top: 30px; padding-top: 20px; border-top: 1px dashed #e2e8f0; }
    .whatsapp-share p { margin-bottom: 10px; font-weight: 600; color: #475569; }
    .share-form { display: flex; gap: 8px; justify-content: center; }
    .share-form input { max-width: 180px; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }

    @media print {
        @page { margin: 10mm; }
        .sidebar, .header, .no-print, .whatsapp-share { display: none !important; }
        .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
        .receipt-container { padding: 0; }
        .receipt-card { box-shadow: none; border: none; width: 100%; }
    }

    @media print {
        body.print-thermal .receipt-card { width: 72mm !important; padding: 6mm !important; }
        body.print-thermal .receipt-container { justify-content: flex-start; }
        body.print-thermal .receipt-table th:nth-child(3),
        body.print-thermal .receipt-table td:nth-child(3) { display: none; }
        body.print-thermal .total-row td { font-size: 16px; }
        body.print-thermal .receipt-table td { padding: 8px 0; font-size: 13px; }
        body.print-thermal .receipt-header { padding-bottom: 12px; margin-bottom: 12px; }
        body.print-thermal .receipt-footer { margin-top: 16px; }
        @page { size: 80mm auto; margin: 4mm; }
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>