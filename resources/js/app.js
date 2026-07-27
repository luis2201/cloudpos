const openDialog = (dialog) => {
    if (dialog instanceof HTMLDialogElement && !dialog.open) {
        dialog.showModal();
    }
};

document.querySelectorAll('[data-dialog-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        openDialog(document.getElementById(trigger.dataset.dialogOpen));
    });
});

document.querySelectorAll('[data-dialog-close]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
        trigger.closest('dialog')?.close();
    });
});

document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

document.querySelectorAll('dialog[data-auto-open]').forEach(openDialog);

document.querySelector('[data-sidebar-open]')?.addEventListener('click', () => {
    document.body.classList.add('sidebar-open');
});

document.querySelector('[data-sidebar-close]')?.addEventListener('click', () => {
    document.body.classList.remove('sidebar-open');
});

document.querySelectorAll('.sidebar a').forEach((link) => {
    link.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
    }
});

document.querySelectorAll('[data-initial-stock-form]').forEach((form) => {
    const product = form.querySelector('[data-initial-product]');
    const lines = form.querySelector('[data-initial-lines]');
    const template = form.querySelector('[data-initial-line-template]');
    const addButton = form.querySelector('[data-initial-add]');
    const total = form.querySelector('[data-initial-total]');
    const formatter = new Intl.NumberFormat('es-EC');

    if (!(product instanceof HTMLSelectElement) || !(lines instanceof HTMLElement) || !(template instanceof HTMLTemplateElement)) {
        return;
    }

    const lineElements = () => [...lines.querySelectorAll('[data-initial-line]')];

    const reindex = () => {
        lineElements().forEach((line, index) => {
            const packageSelect = line.querySelector('[data-initial-package]');
            const quantityInput = line.querySelector('[data-initial-quantity]');

            if (packageSelect instanceof HTMLSelectElement) {
                packageSelect.name = `lines[${index}][product_package_id]`;
            }

            if (quantityInput instanceof HTMLInputElement) {
                quantityInput.name = `lines[${index}][package_quantity]`;
            }
        });
    };

    const refreshPackageOptions = () => {
        const selectedProduct = product.value;
        const selects = lineElements()
            .map((line) => line.querySelector('[data-initial-package]'))
            .filter((select) => select instanceof HTMLSelectElement);
        const selectedPackages = selects.map((select) => select.value);

        selects.forEach((select, selectIndex) => {
            [...select.options].forEach((option) => {
                if (option.value === '') {
                    option.disabled = false;
                    option.hidden = false;

                    return;
                }

                const belongsToProduct = selectedProduct !== '' && option.dataset.productId === selectedProduct;
                const usedOnAnotherLine = selectedPackages.some((value, index) => (
                    index !== selectIndex && value !== '' && value === option.value
                ));
                option.disabled = !belongsToProduct || usedOnAnotherLine;
                option.hidden = !belongsToProduct;
            });

            const selectedOption = select.selectedOptions[0];

            if (selectedOption?.value && selectedOption.dataset.productId !== selectedProduct) {
                select.value = '';
            }
        });
    };

    const recalculate = () => {
        let totalUnits = 0;

        lineElements().forEach((line) => {
            const packageSelect = line.querySelector('[data-initial-package]');
            const quantityInput = line.querySelector('[data-initial-quantity]');
            const subtotal = line.querySelector('[data-initial-subtotal]');
            const factor = Number(packageSelect?.selectedOptions[0]?.dataset.factor ?? 0);
            const quantity = Number(quantityInput?.value ?? 0);
            const lineUnits = Number.isFinite(factor * quantity) ? factor * quantity : 0;
            totalUnits += lineUnits;

            if (subtotal instanceof HTMLOutputElement) {
                subtotal.value = `${formatter.format(lineUnits)} unidades`;
            }
        });

        if (total instanceof HTMLOutputElement) {
            total.value = `${formatter.format(totalUnits)} unidades`;
        }
    };

    const refreshRemoveButtons = () => {
        const currentLines = lineElements();

        currentLines.forEach((line) => {
            const removeButton = line.querySelector('[data-initial-remove]');

            if (removeButton instanceof HTMLButtonElement) {
                removeButton.disabled = currentLines.length === 1;
            }
        });

        if (addButton instanceof HTMLButtonElement) {
            addButton.disabled = currentLines.length >= 20;
        }
    };

    const bindLine = (line) => {
        line.querySelector('[data-initial-package]')?.addEventListener('change', () => {
            refreshPackageOptions();
            recalculate();
        });
        line.querySelector('[data-initial-quantity]')?.addEventListener('input', recalculate);
        line.querySelector('[data-initial-remove]')?.addEventListener('click', () => {
            if (lineElements().length === 1) {
                return;
            }

            line.remove();
            reindex();
            refreshPackageOptions();
            refreshRemoveButtons();
            recalculate();
        });
    };

    lineElements().forEach(bindLine);
    product.addEventListener('change', () => {
        refreshPackageOptions();
        recalculate();
    });
    addButton?.addEventListener('click', () => {
        if (lineElements().length >= 20) {
            return;
        }

        const fragment = template.content.cloneNode(true);
        const newLine = fragment.querySelector('[data-initial-line]');

        if (!(newLine instanceof HTMLElement)) {
            return;
        }

        lines.append(fragment);
        bindLine(newLine);
        reindex();
        refreshPackageOptions();
        refreshRemoveButtons();
        recalculate();
    });

    reindex();
    refreshPackageOptions();
    refreshRemoveButtons();
    recalculate();
});
