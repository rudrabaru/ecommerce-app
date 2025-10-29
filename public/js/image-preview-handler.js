/**
 * Image Preview Handler
 * Reusable module for handling image preview and file name display in modals
 */

window.ImagePreviewHandler = {
    /**
     * Convert via.placeholder.com URLs to placehold.co
     * @param {string} url - The image URL
     * @param {string} defaultText - Default text for placeholder
     * @param {string} defaultDimensions - Default dimensions
     * @returns {string} - Converted URL
     */
    convertPlaceholderUrl: function(url, defaultText = 'Image', defaultDimensions = '600x600') {
        if (!url || !url.includes('via.placeholder.com')) {
            return url;
        }

        try {
            const urlParts = url.split('/');
            let dimensions = defaultDimensions;
            let color = '0066cc';
            let text = defaultText;

            // Extract dimensions (e.g., /600x400.png or /600x400/)
            if (urlParts[3]) {
                dimensions = urlParts[3].replace('.png', '').replace('.jpg', '').replace('.jpeg', '');
            }

            // Extract color if present
            if (urlParts[4]) {
                color = urlParts[4].split('?')[0].split('.')[0];
            }

            // Extract text from query string
            const queryMatch = url.match(/[?&]text=([^&]+)/);
            if (queryMatch) {
                text = decodeURIComponent(queryMatch[1].replace(/\+/g, ' '));
            } else if (urlParts[5]) {
                text = decodeURIComponent(urlParts[5].split('?')[0]);
            }

            // Build placehold.co URL
            return `https://placehold.co/${dimensions}/${color}/ffffff?text=${encodeURIComponent(text)}`;
        } catch (e) {
            console.warn('Error converting placeholder URL:', e);
            return `https://placehold.co/${defaultDimensions}/cccccc/666666?text=${encodeURIComponent(defaultText)}`;
        }
    },

    /**
     * Get image source with proper handling for both uploaded and placeholder images
     * @param {string} image - Image path or URL
     * @param {string} defaultText - Default text for placeholder fallback
     * @returns {string} - Processed image URL
     */
    getImageSrc: function(image, defaultText = 'Image') {
        if (!image) {
            return `https://placehold.co/600x600/cccccc/666666?text=${encodeURIComponent(defaultText)}`;
        }

        // Handle absolute URLs
        if (image.startsWith('http://') || image.startsWith('https://')) {
            return this.convertPlaceholderUrl(image, defaultText);
        }

        // Handle relative paths (uploaded images)
        if (image.startsWith('storage/')) {
            return '/' + image;
        }

        // Default: assume it's a storage path
        return '/storage/' + image.replace(/^\/+/, '');
    },

    /**
     * Extract file name from path or URL
     * @param {string} imagePath - Image path or URL
     * @returns {string} - File name
     */
    getFileName: function(imagePath) {
        if (!imagePath) return 'image';
        
        // Remove query string
        const pathWithoutQuery = imagePath.split('?')[0];
        
        // Get last part of path
        const fileName = pathWithoutQuery.split('/').pop();
        
        // Decode if it's a URL-encoded name
        try {
            return decodeURIComponent(fileName);
        } catch (e) {
            return fileName;
        }
    },

    /**
     * Setup image preview for a file input
     * @param {Object} config - Configuration object
     * @param {string} config.fileInputId - ID of file input element
     * @param {string} config.fileNameDisplayId - ID of element to show file name
     * @param {string} config.imagePreviewId - ID of img element for preview
     * @param {string} config.imageContainerId - ID of container element (optional)
     * @param {Function} config.onChange - Optional callback when file changes
     */
    setupFileInput: function(config) {
        const {
            fileInputId,
            fileNameDisplayId,
            imagePreviewId,
            imageContainerId,
            onChange
        } = config;

        const fileInput = document.getElementById(fileInputId);
        const fileNameDisplay = document.getElementById(fileNameDisplayId);
        const imagePreview = document.getElementById(imagePreviewId);
        const imageContainer = imageContainerId ? document.getElementById(imageContainerId) : null;

        if (!fileInput || !fileNameDisplay || !imagePreview) {
            console.warn('ImagePreviewHandler: Required elements not found', config);
            return null;
        }

        const handler = function(e) {
            if (e.target.files && e.target.files.length > 0) {
                const file = e.target.files[0];
                
                // Show file name
                fileNameDisplay.textContent = 'Selected file: ' + file.name;
                fileNameDisplay.style.display = 'block';
                
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                    if (imageContainer) {
                        imageContainer.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);

                // Call optional callback
                if (typeof onChange === 'function') {
                    onChange(file);
                }
            } else {
                // No file selected - hide preview
                fileNameDisplay.style.display = 'none';
                imagePreview.classList.add('d-none');
                if (imageContainer) {
                    imageContainer.style.display = 'none';
                }
            }
        };

        // Remove existing listeners and add new one
        const newFileInput = fileInput.cloneNode(true);
        fileInput.parentNode.replaceChild(newFileInput, fileInput);
        newFileInput.addEventListener('change', handler);

        return newFileInput;
    },

    /**
     * Show existing image in preview (for edit mode)
     * @param {Object} config - Configuration object
     * @param {string} config.imagePath - Path or URL of existing image
     * @param {string} config.fileNameDisplayId - ID of element to show file name
     * @param {string} config.imagePreviewId - ID of img element for preview
     * @param {string} config.imageContainerId - ID of container element (optional)
     * @param {string} config.defaultText - Default text for placeholder
     * @param {string} config.fallbackUrl - Fallback URL if image fails to load
     */
    showExistingImage: function(config) {
        const {
            imagePath,
            fileNameDisplayId,
            imagePreviewId,
            imageContainerId,
            defaultText = 'Image',
            fallbackUrl
        } = config;

        if (!imagePath) return;

        const fileNameDisplay = document.getElementById(fileNameDisplayId);
        const imagePreview = document.getElementById(imagePreviewId);
        const imageContainer = imageContainerId ? document.getElementById(imageContainerId) : null;

        // Get processed image source
        const imageSrc = this.getImageSrc(imagePath, defaultText);

        // Set image source with error handling
        $(imagePreview)
            .attr('src', imageSrc)
            .removeClass('d-none')
            .off('error')
            .on('error', function() {
                const fallback = fallbackUrl || `https://placehold.co/600x600/cccccc/666666?text=${encodeURIComponent(defaultText)}`;
                $(this).attr('src', fallback);
            });

        if (imageContainer) {
            imageContainer.style.display = 'block';
        }

        // Show file name
        if (fileNameDisplay) {
            const fileName = this.getFileName(imagePath);
            fileNameDisplay.textContent = 'Current image: ' + fileName;
            fileNameDisplay.style.display = 'block';
        }
    },

    /**
     * Reset image preview (clear all displays)
     * @param {Object} config - Configuration object
     * @param {string} config.fileNameDisplayId - ID of element showing file name
     * @param {string} config.imagePreviewId - ID of img element for preview
     * @param {string} config.imageContainerId - ID of container element (optional)
     */
    resetPreview: function(config) {
        const {
            fileNameDisplayId,
            imagePreviewId,
            imageContainerId
        } = config;

        const fileNameDisplay = document.getElementById(fileNameDisplayId);
        const imagePreview = document.getElementById(imagePreviewId);
        const imageContainer = imageContainerId ? document.getElementById(imageContainerId) : null;

        if (fileNameDisplay) {
            fileNameDisplay.style.display = 'none';
            fileNameDisplay.textContent = '';
        }

        if (imagePreview) {
            imagePreview.classList.add('d-none');
            imagePreview.src = '';
        }

        if (imageContainer) {
            imageContainer.style.display = 'none';
        }
    }
};
