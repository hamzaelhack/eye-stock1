// Products page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filters
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter = document.getElementById('stockFilter');
    const statusFilter = document.getElementById('statusFilter');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();
        const selectedStock = stockFilter.value;
        const selectedStatus = statusFilter.value;

        document.querySelectorAll('.product-card').forEach(card => {
            const productName = card.querySelector('.card-title').textContent.toLowerCase();
            const productCategory = card.querySelector('.product-category').textContent.toLowerCase();
            const productQuantity = parseInt(card.querySelector('.product-quantity').dataset.quantity);
            const productStatus = card.querySelector('.product-status').dataset.status;

            let showCard = true;

            // Apply search filter
            if (searchTerm && !productName.includes(searchTerm)) {
                showCard = false;
            }

            // Apply category filter
            if (selectedCategory && !productCategory.includes(selectedCategory)) {
                showCard = false;
            }

            // Apply stock filter
            if (selectedStock) {
                switch(selectedStock) {
                    case 'out':
                        if (productQuantity > 0) showCard = false;
                        break;
                    case 'low':
                        if (productQuantity > 10) showCard = false;
                        break;
                    case 'in':
                        if (productQuantity <= 0) showCard = false;
                        break;
                }
            }

            // Apply status filter
            if (selectedStatus && productStatus !== selectedStatus) {
                showCard = false;
            }

            card.style.display = showCard ? '' : 'none';
        });
    }

    // Add event listeners to filters
    searchInput?.addEventListener('input', filterProducts);
    categoryFilter?.addEventListener('change', filterProducts);
    stockFilter?.addEventListener('change', filterProducts);
    statusFilter?.addEventListener('change', filterProducts);

    // Image Preview
    const imageInput = document.getElementById('productImage');
    const imagePreview = document.getElementById('imagePreview');

    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create image preview
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" 
                             class="img-thumbnail" 
                             style="max-height: 200px; width: auto;">`;
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.innerHTML = '';
            }
        });
    }

    // Product Form Submission
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';

                const response = await fetch('../api/products/save.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Erreur lors de l\'enregistrement du produit');
                }

                // Show success message and reload
                showAlert('success', 'Produit enregistré avec succès');
                setTimeout(() => location.reload(), 1500);

            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Enregistrer';
            }
        });
    }
});

// Edit Product
function editProduct(id) {
    fetch(`../api/products/get.php?id=${id}`)
        .then(response => response.json())
        .then(product => {
            // Fill form with product data
            document.getElementById('productId').value = product.id;
            document.querySelector('select[name="category"]').value = product.category;
            document.querySelector('input[name="name"]').value = product.name;
            document.querySelector('input[name="buy_price"]').value = product.buy_price;
            document.querySelector('input[name="sell_price"]').value = product.sell_price;
            document.querySelector('input[name="quantity"]').value = product.quantity;
            document.querySelector('input[name="min_quantity"]').value = product.min_quantity;
            document.querySelector('textarea[name="notes"]').value = product.notes || '';

            if (product.image) {
                document.getElementById('imagePreview').innerHTML = `
                    <img src="../uploads/products/${product.image}" 
                         class="img-thumbnail" 
                         style="max-height: 200px; width: auto;">`;
            }

            // Update modal title
            document.getElementById('modalTitle').textContent = 'Modifier le Produit';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        })
        .catch(error => showAlert('danger', 'Erreur lors du chargement du produit'));
}

// Update Stock
function updateStock(id) {
    fetch(`../api/products/get.php?id=${id}`)
        .then(response => response.json())
        .then(product => {
            document.getElementById('stockProductId').value = product.id;
            document.getElementById('currentStock').value = product.quantity;
            
            const modal = new bootstrap.Modal(document.getElementById('stockModal'));
            modal.show();
        })
        .catch(error => showAlert('danger', 'Erreur lors du chargement du produit'));
}

// Delete Product
function deleteProduct(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        fetch(`../api/products/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showAlert('success', 'Produit supprimé avec succès');
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(result.message || 'Erreur lors de la suppression');
            }
        })
        .catch(error => showAlert('danger', error.message));
    }
}

// Stock Form Submission
const stockForm = document.getElementById('stockForm');
if (stockForm) {
    stockForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mise à jour...';

            const response = await fetch('../api/products/update_stock.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Erreur lors de la mise à jour du stock');
            }

            // Show success message and reload
            showAlert('success', 'Stock mis à jour avec succès');
            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            showAlert('danger', error.message);
        } finally {
            // Reset loading state
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Mettre à jour';
        }
    });
}

// Utility function to show alerts
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => alertDiv.remove(), 5000);
}
