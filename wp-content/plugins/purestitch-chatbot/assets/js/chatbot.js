/**
 * PureStitch WooCommerce AI Chatbot - Frontend JavaScript
 * 
 * This script handles the chatbot interface interactions, including:
 * - Opening/closing the chatbot dialog
 * - Sending messages to the backend
 * - Displaying responses from the chatbot
 * - Handling product lists and product details
 */

jQuery(document).ready(function($) {
    // DOM Elements
    const chatbotIcon = $('#purestitch-chatbot-icon');
    const chatbotDialog = $('#purestitch-chatbot-dialog');
    const chatbotClose = $('#purestitch-chatbot-close');
    const chatbotInput = $('#purestitch-chatbot-input');
    const chatbotSend = $('#purestitch-chatbot-send');
    const chatbotMessages = $('#purestitch-chatbot-messages');
    const chatbotTyping = $('.purestitch-chatbot-typing');
    
    // Variables
    let isOpen = false;
    const restUrl = purestitchChatbot.rest_url;
    const nonce = purestitchChatbot.nonce;
    const isAiEnabled = purestitchChatbot.is_ai_enabled === 'yes';
    const siteName = purestitchChatbot.site_name;
    
    // Functions
    function toggleChatbot() {
        isOpen = !isOpen;
        if (isOpen) {
            chatbotDialog.slideDown(300);
            setTimeout(() => {
                chatbotInput.focus();
                scrollToBottom();
            }, 300);
        } else {
            chatbotDialog.slideUp(300);
        }
    }
    
    function scrollToBottom() {
        chatbotMessages.scrollTop(chatbotMessages[0].scrollHeight);
    }
    
    function showTypingIndicator() {
        chatbotTyping.show();
        scrollToBottom();
    }
    
    function hideTypingIndicator() {
        chatbotTyping.hide();
    }
    
    function addMessage(message, sender = 'user') {
        const messageClass = sender === 'user' ? 'user' : 'bot';
        const messageHtml = `<div class="purestitch-chatbot-message ${messageClass}">${message}</div>`;
        chatbotMessages.append(messageHtml);
        scrollToBottom();
    }
    
    function sendMessage() {
        const message = chatbotInput.val().trim();
        
        if (message === '') return;
        
        // Add user message to chat
        addMessage(message, 'user');
        
        // Clear input
        chatbotInput.val('');
        
        // Show typing indicator
        showTypingIndicator();
        
        // Send message to server
        $.ajax({
            url: restUrl + 'query',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            data: {
                query: message
            },
            success: function(response) {
                handleResponse(response);
            },
            error: function(error) {
                console.error('Chatbot error:', error);
                hideTypingIndicator();
                addMessage("Sorry, I'm having trouble connecting right now. Please try again later.", 'bot');
            }
        });
    }
    
    function handleResponse(response) {
        hideTypingIndicator();
        
        if (!response) {
            addMessage("Sorry, I'm having trouble understanding that. Could you try rephrasing?", 'bot');
            return;
        }
        
        // Handle different response types
        switch (response.type) {
            case 'product_list':
                handleProductList(response);
                break;
            case 'ai_response':
                handleAiResponse(response);
                break;
            case 'general':
            default:
                addMessage(response.message, 'bot');
                break;
        }
    }
    
    function handleAiResponse(response) {
        // Format the message with line breaks
        const formattedMessage = response.message.replace(/\n/g, '<br>');
        addMessage(formattedMessage, 'bot');
        
        // If function data includes products, display them
        if (response.function_used === 'search_products' && response.function_data && 
            response.function_data.products && response.function_data.products.length > 0) {
            displayProductList(response.function_data.products);
        }
    }
    
    function handleProductList(response) {
        addMessage(response.message, 'bot');
        
        if (response.products && response.products.length > 0) {
            displayProductList(response.products);
        }
    }
    
    function displayProductList(products) {
        let productsHtml = '<div class="purestitch-product-list">';
        
        products.forEach(product => {
            productsHtml += `
                <div class="purestitch-product-item" data-product-id="${product.id}">
                    <div class="purestitch-product-image">
                        <img src="${product.image}" alt="${product.name}">
                    </div>
                    <div class="purestitch-product-info">
                        <h4>${product.name}</h4>
                        <p class="purestitch-product-price">${product.price}</p>
                        <p class="purestitch-product-stock ${product.stock_status}">${formatStockStatus(product.stock_status)}</p>
                        <div class="purestitch-product-actions">
                            <a href="${product.url}" class="purestitch-view-product" target="_blank">View Product</a>
                            <button class="purestitch-product-details" data-product-id="${product.id}">Details</button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        productsHtml += '</div>';
        chatbotMessages.append(productsHtml);
        scrollToBottom();
    }
    
    function formatStockStatus(status) {
        switch (status) {
            case 'instock':
                return 'In Stock';
            case 'outofstock':
                return 'Out of Stock';
            case 'onbackorder':
                return 'On Backorder';
            default:
                return status;
        }
    }
    
    function getProductDetails(productId) {
        showTypingIndicator();
        
        $.ajax({
            url: restUrl + 'product/' + productId,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function(product) {
                hideTypingIndicator();
                displayProductDetails(product);
            },
            error: function(error) {
                hideTypingIndicator();
                addMessage("Sorry, I couldn't retrieve the product details. Please try again.", 'bot');
            }
        });
    }
    
    function displayProductDetails(product) {
        let detailsHtml = `
            <div class="purestitch-product-details">
                <h3>${product.name}</h3>
                <div class="purestitch-product-image-large">
                    <img src="${product.image}" alt="${product.name}">
                </div>
                <div class="purestitch-product-info-detailed">
                    <p class="purestitch-product-price">${product.price}</p>
                    <p class="purestitch-product-stock ${product.stock_status}">${formatStockStatus(product.stock_status)}</p>
        `;
        
        if (product.stock_quantity) {
            detailsHtml += `<p class="purestitch-stock-quantity">${product.stock_quantity} in stock</p>`;
        }
        
        if (product.short_description) {
            detailsHtml += `<div class="purestitch-product-description">${product.short_description}</div>`;
        }
        
        // Show variations if available
        if (product.variations && product.variations.length > 0) {
            detailsHtml += '<div class="purestitch-product-variations">';
            detailsHtml += '<h4>Available Options:</h4>';
            
            // Group similar attributes
            const attributeGroups = {};
            
            product.variations.forEach(variation => {
                Object.keys(variation.attributes).forEach(attrName => {
                    if (!attributeGroups[attrName]) {
                        attributeGroups[attrName] = new Set();
                    }
                    attributeGroups[attrName].add(variation.attributes[attrName]);
                });
            });
            
            // Display attribute options
            Object.keys(attributeGroups).forEach(attrName => {
                detailsHtml += `<p><strong>${attrName}:</strong> ${Array.from(attributeGroups[attrName]).join(', ')}</p>`;
            });
            
            detailsHtml += '</div>';
        }
        
        detailsHtml += `
                    <div class="purestitch-product-actions">
                        <a href="${product.url}" class="purestitch-view-product" target="_blank">View Full Details</a>
                    </div>
                </div>
            </div>
        `;
        
        addMessage(detailsHtml, 'bot');
    }
    
    // Event Listeners
    chatbotIcon.on('click', toggleChatbot);
    chatbotClose.on('click', toggleChatbot);
    
    chatbotSend.on('click', sendMessage);
    
    chatbotInput.on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            sendMessage();
        }
    });
    
    // Delegate event for dynamically added product detail buttons
    $(document).on('click', '.purestitch-product-details', function() {
        const productId = $(this).data('product-id');
        getProductDetails(productId);
    });
    
    // Clear history function (for debugging)
    function clearChatHistory() {
        $.ajax({
            url: restUrl + 'clear-history',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
            success: function() {
                console.log('Chat history cleared');
            }
        });
    }
    
    // Add window resize handler to adjust chatbot position if needed
    $(window).resize(function() {
        if (isOpen) {
            scrollToBottom();
        }
    });
});