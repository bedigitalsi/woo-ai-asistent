/**
 * Chat widget frontend JavaScript
 *
 * @package AI_Store_Assistant
 */

(function() {
	'use strict';

	// Check if required data is available
	if ( typeof asaChatData === 'undefined' ) {
		return;
	}

	const chatButton = document.getElementById( 'asa-chat-button' );
	const chatPanel = document.getElementById( 'asa-chat-panel' );
	const chatClose = document.getElementById( 'asa-chat-close' );
	const chatMessages = document.getElementById( 'asa-chat-messages' );
	const chatInput = document.getElementById( 'asa-chat-input' );
	const chatSend = document.getElementById( 'asa-chat-send' );

	if ( ! chatButton || ! chatPanel || ! chatMessages || ! chatInput || ! chatSend ) {
		return;
	}

	let messages = [];
	let isLoading = false;

	// Initialize chat
	function init() {
		chatButton.addEventListener( 'click', toggleChat );
		chatClose.addEventListener( 'click', closeChat );
		chatSend.addEventListener( 'click', sendMessage );
		chatInput.addEventListener( 'keypress', function( e ) {
			if ( e.key === 'Enter' && ! e.shiftKey ) {
				e.preventDefault();
				sendMessage();
			}
		});

		// Set widget title
		if ( asaChatData.widgetTitle ) {
			const titleElement = document.getElementById( 'asa-chat-title' );
			if ( titleElement ) {
				titleElement.textContent = asaChatData.widgetTitle;
			}
		}

		// Add welcome message
		const welcomeMessage = asaChatData.welcomeMessage || 'Hello! How can I help you today?';
		addMessage( 'assistant', welcomeMessage );
	}

	// Toggle chat panel
	function toggleChat() {
		if ( chatPanel.style.display === 'none' ) {
			openChat();
		} else {
			closeChat();
		}
	}

	// Open chat
	function openChat() {
		chatPanel.style.display = 'flex';
		chatButton.style.display = 'none';
		chatInput.focus();
		scrollToBottom();
	}

	// Close chat
	function closeChat() {
		chatPanel.style.display = 'none';
		chatButton.style.display = 'flex';
	}

	// Add message to chat
	function addMessage( role, content, products = [] ) {
		const messageDiv = document.createElement( 'div' );
		messageDiv.className = 'asa-chat-message asa-chat-message-' + role;

		if ( role === 'user' ) {
			messageDiv.textContent = content;
		} else {
			const contentDiv = document.createElement( 'div' );
			contentDiv.className = 'asa-chat-message-content';
			contentDiv.textContent = content;
			messageDiv.appendChild( contentDiv );

			// Add products if available
			if ( products && products.length > 0 ) {
				const productsDiv = document.createElement( 'div' );
				productsDiv.className = 'asa-chat-products';

				// Add class for side-by-side layout when there are exactly 2 products
				if ( products.length === 2 ) {
					productsDiv.className += ' asa-chat-products-two';
				}

				products.forEach( function( product ) {
					const productCard = document.createElement( 'div' );
					productCard.className = 'asa-chat-product-card';

					if ( product.image ) {
						const img = document.createElement( 'img' );
						img.src = product.image;
						img.alt = product.name;
						img.className = 'asa-chat-product-image';
						productCard.appendChild( img );
					}

					const productInfo = document.createElement( 'div' );
					productInfo.className = 'asa-chat-product-info';

					const productName = document.createElement( 'div' );
					productName.className = 'asa-chat-product-name';
					productName.textContent = product.name;
					productInfo.appendChild( productName );

					// Price removed - only showing product name

					const productButton = document.createElement( 'a' );
					productButton.href = product.url;
					productButton.target = '_blank';
					productButton.className = 'asa-chat-product-button';
					productButton.textContent = asaChatData.i18n.viewProduct;
					productInfo.appendChild( productButton );

					productCard.appendChild( productInfo );
					productsDiv.appendChild( productCard );
				});

				messageDiv.appendChild( productsDiv );
			}
		}

		chatMessages.appendChild( messageDiv );
		scrollToBottom();
	}

	// Show loading indicator
	function showLoading() {
		const loadingDiv = document.createElement( 'div' );
		loadingDiv.className = 'asa-chat-message asa-chat-message-assistant asa-chat-loading';
		loadingDiv.id = 'asa-chat-loading';
		loadingDiv.innerHTML = '<div class="asa-chat-message-content">' + 
			'<span class="asa-chat-loading-dots">' +
			'<span>.</span><span>.</span><span>.</span>' +
			'</span></div>';
		chatMessages.appendChild( loadingDiv );
		scrollToBottom();
	}

	// Remove loading indicator
	function removeLoading() {
		const loading = document.getElementById( 'asa-chat-loading' );
		if ( loading ) {
			loading.remove();
		}
	}

	// Scroll to bottom
	function scrollToBottom() {
		chatMessages.scrollTop = chatMessages.scrollHeight;
	}

	// Send message
	function sendMessage() {
		const message = chatInput.value.trim();

		if ( ! message || isLoading ) {
			return;
		}

		// Add user message
		addMessage( 'user', message );
		messages.push( { role: 'user', content: message } );

		// Clear input
		chatInput.value = '';

		// Show loading
		isLoading = true;
		showLoading();
		chatSend.disabled = true;
		chatInput.disabled = true;

		// Prepare request
		const requestData = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': asaChatData.nonce,
			},
			body: JSON.stringify( {
				messages: messages.slice( -10 ), // Send last 10 messages
			} ),
		};

		// Send request
		fetch( asaChatData.restUrl, requestData )
			.then( function( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Network response was not ok' );
				}
				return response.json();
			})
			.then( function( data ) {
				removeLoading();

				if ( data.assistant_reply ) {
					addMessage( 'assistant', data.assistant_reply, data.products || [] );
					messages.push( { role: 'assistant', content: data.assistant_reply } );

					// Check if AI wants to create an order
					if ( data.order && data.order.products && data.order.email && data.order.phone && data.order.address ) {
						console.log( 'Order data detected:', data.order );
						createOrder( data.order );
					} else if ( data.order ) {
						console.log( 'Order data incomplete:', data.order );
					}
				} else {
					addMessage( 'assistant', asaChatData.i18n.error );
				}
			})
			.catch( function( error ) {
				removeLoading();
				addMessage( 'assistant', asaChatData.i18n.apiError );
				console.error( 'Chat error:', error );
			})
			.finally( function() {
				isLoading = false;
				chatSend.disabled = false;
				chatInput.disabled = false;
				chatInput.focus();
			});
	}

	// Create order
	function createOrder( orderData ) {
		// Show loading
		isLoading = true;
		showLoading();
		chatSend.disabled = true;
		chatInput.disabled = true;

		const requestData = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': asaChatData.nonce,
			},
			body: JSON.stringify( {
				products: orderData.products,
				customer: {
					email: orderData.email,
					phone: orderData.phone,
					address: orderData.address,
				},
			} ),
		};

		fetch( asaChatData.restUrl.replace( '/chat', '/create-order' ), requestData )
			.then( function( response ) {
				if ( ! response.ok ) {
					return response.json().then( function( errorData ) {
						throw errorData;
					});
				}
				return response.json();
			})
			.then( function( data ) {
				removeLoading();

				if ( data.order_id ) {
					const currencySymbol = data.currency_symbol || data.currency || '';
					const subtotal = data.subtotal || 0;
					const shipping = data.shipping_total || 0;
					const total = data.total || 0;
					
					let successMessage = 'Order #' + data.order_id + ' has been created successfully!\n\n';
					successMessage += 'Order Details:\n';
					successMessage += '- Subtotal: ' + currencySymbol + subtotal.toFixed(2) + '\n';
					if ( shipping > 0 ) {
						successMessage += '- Shipping: ' + currencySymbol + shipping.toFixed(2) + '\n';
					}
					successMessage += '- Total: ' + currencySymbol + total.toFixed(2) + '\n';
					successMessage += '- Payment: Cash on Delivery\n\n';
					successMessage += 'Thank you for your order!';
					
					addMessage( 'assistant', successMessage );
					messages.push( { role: 'assistant', content: successMessage } );
				} else if ( data.code ) {
					// Error from server
					const errorMessage = data.message || asaChatData.i18n.orderError || 'Unable to create order. Please try again.';
					addMessage( 'assistant', errorMessage );
				} else {
					addMessage( 'assistant', asaChatData.i18n.orderError || 'Unable to create order. Please try again.' );
				}
			})
			.catch( function( error ) {
				removeLoading();
				addMessage( 'assistant', asaChatData.i18n.orderError || 'Unable to create order. Please try again.' );
				console.error( 'Order creation error:', error );
			})
			.finally( function() {
				isLoading = false;
				chatSend.disabled = false;
				chatInput.disabled = false;
				chatInput.focus();
			});
	}

	// Initialize when DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();

