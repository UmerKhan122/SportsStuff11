document.addEventListener('DOMContentLoaded', () => {
    // Quantity logic
    const qtyBtnMinus = document.getElementById('qty-minus');
    const qtyBtnPlus = document.getElementById('qty-plus');
    const qtyInput = document.getElementById('qty');
    const customizationYes = document.getElementById('cust_yes');
    const customizationNo = document.getElementById('cust_no');
    const customizationDetails = document.getElementById('customization-details-group');
    const totalAmountSpan = document.getElementById('total-amount-display');

    if (qtyBtnMinus && qtyBtnPlus && qtyInput) {
        qtyBtnMinus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                updateTotal();
            }
        });

        qtyBtnPlus.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            qtyInput.value = val + 1;
            updateTotal();
        });
    }

    if (customizationYes && customizationNo && customizationDetails) {
        customizationYes.addEventListener('change', () => {
            if(customizationYes.checked) {
                customizationDetails.style.display = 'block';
                updateTotal();
            }
        });
        customizationNo.addEventListener('change', () => {
            if(customizationNo.checked) {
                customizationDetails.style.display = 'none';
                updateTotal();
            }
        });
    }

    function updateTotal() {
        if (!totalAmountSpan) return;
        const basePrice = parseFloat(totalAmountSpan.dataset.basePrice) || 0;
        const custPrice = parseFloat(totalAmountSpan.dataset.custPrice) || 0;
        
        let qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
        let total = basePrice * qty;

        if (customizationYes && customizationYes.checked) {
            total += (custPrice * qty);
        }

        totalAmountSpan.textContent = '₹' + total.toFixed(2);
    }

    // Countdown timers for limited time offers
    const countdowns = document.querySelectorAll('.countdown');
    if (countdowns.length > 0) {
        setInterval(() => {
            const now = new Date().getTime();
            countdowns.forEach(el => {
                const end = new Date(el.dataset.end).getTime();
                const distance = end - now;

                if (distance < 0) {
                    el.innerHTML = "EXPIRED";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                
                el.innerHTML = `${days.toString().padStart(2, '0')} DAYS ${hours.toString().padStart(2, '0')} HOURS ${minutes.toString().padStart(2, '0')} MINS`;
            });
        }, 1000);
    }
});
