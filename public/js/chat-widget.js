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
	let mode = 'chat'; // 'chat' or 'checkout'
	
	// Checkout data structure
	let chatOrder = {
		products: [],
		customer: {
			email: null,
			phone: null,
			first_name: null,
			last_name: null,
			address_1: null,
			city: null,
			postcode: null,
			country: 'SI' // Default, can be changed
		}
	};

	// Checkout flow steps
	const checkoutSteps = [
		{ field: 'email', question: 'Great! I\'ll prepare your order. First, what is your email address?', validator: validateEmail },
		{ field: 'phone', question: 'Thanks! What is your phone number?', validator: validatePhone },
		{ field: 'first_name', question: 'What is your first name?', validator: validateName },
		{ field: 'last_name', question: 'What is your last name?', validator: validateName },
		{ field: 'address_1', question: 'What is your street address and house number?', validator: validateAddress },
		{ field: 'city', question: 'What city are you in?', validator: validateCity },
		{ field: 'postcode', question: 'What is your postal code?', validator: validatePostcode }
	];

	let currentCheckoutStep = 0;

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
		messages.push( { role: 'assistant', content: welcomeMessage } );

		// Load checkout data from sessionStorage if available
		loadCheckoutData();
	}

	// Validation functions
	function validateEmail( email ) {
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test( email.trim() );
	}

	function validatePhone( phone ) {
		// Basic validation - at least 6 characters
		return phone.trim().length >= 6;
	}

	function validateName( name ) {
		return name.trim().length >= 2;
	}

	function validateAddress( address ) {
		return address.trim().length >= 5;
	}

	function validateCity( city ) {
		return city.trim().length >= 2;
	}

	function validatePostcode( postcode ) {
		return postcode.trim().length >= 3;
	}

	// Toggle chat panel
	function toggleChat() {
		chatPanel.classList.toggle( 'asa-chat-open' );
		if ( chatPanel.classList.contains( 'asa-chat-open' ) ) {
			chatInput.focus();
			scrollToBottom();
		}
	}

	// Close chat
	function closeChat() {
		chatPanel.classList.remove( 'asa-chat-open' );
	}

	// Add message to chat
	function addMessage( role, content, products ) {
		const messageDiv = document.createElement( 'div' );
		messageDiv.className = 'asa-chat-message asa-chat-message-' + role;

		if ( role === 'user' ) {
			messageDiv.textContent = content;
		} else {
			const textDiv = document.createElement( 'div' );
			textDiv.className = 'asa-chat-message-text';
			// Convert newlines to <br>
			textDiv.innerHTML = content.replace( /\n/g, '<br>' );
			messageDiv.appendChild( textDiv );

			// Add products if any
			if ( products && products.length > 0 ) {
				const productsDiv = document.createElement( 'div' );
				productsDiv.className = 'asa-chat-products';
				if ( products.length === 2 ) {
					productsDiv.classList.add( 'asa-chat-products-two' );
				}

				products.forEach( function( product ) {
					const productCard = document.createElement( 'div' );
					productCard.className = 'asa-chat-product';

					if ( product.image ) {
						const img = document.createElement( 'img' );
						img.src = product.image;
						img.alt = product.name;
						productCard.appendChild( img );
					}

					const nameDiv = document.createElement( 'div' );
					nameDiv.className = 'asa-chat-product-name';
					nameDiv.textContent = product.name;
					productCard.appendChild( nameDiv );

					const link = document.createElement( 'a' );
					link.href = product.url;
					link.className = 'asa-chat-product-link';
					link.textContent = asaChatData.i18n.viewProduct || 'View Product';
					link.target = '_blank';
					productCard.appendChild( link );

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
		const loading = document.createElement( 'div' );
		loading.id = 'asa-chat-loading';
		loading.className = 'asa-chat-message asa-chat-message-assistant';
		loading.innerHTML = '<div class="asa-chat-message-text">' + ( asaChatData.i18n.loading || 'Thinking...' ) + '</div>';
		chatMessages.appendChild( loading );
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

	// Check if message indicates order intent
	function checkOrderIntent( message ) {
		const orderKeywords = [
			'order', 'buy', 'purchase', 'checkout', 'place order',
			'i want to order', 'i\'d like to order', 'order now',
			'order these', 'order this', 'proceed with order'
		];
		const lowerMessage = message.toLowerCase();
		return orderKeywords.some( function( keyword ) {
			return lowerMessage.includes( keyword );
		});
	}

	// Extract products from AI response or conversation
	function extractProductsFromConversation() {
		// Try to find product mentions in recent messages
		// This is a simple implementation - you might want to improve it
		const recentMessages = messages.slice( -5 );
		const products = [];

		// Look for product IDs in assistant messages
		recentMessages.forEach( function( msg ) {
			if ( msg.role === 'assistant' && msg.products ) {
				msg.products.forEach( function( product ) {
					if ( product.id ) {
						products.push( { id: product.id, qty: 1 } );
					}
				});
			}
		});

		return products;
	}

	// Start checkout mode
	function startCheckout( products ) {
		mode = 'checkout';
		chatOrder.products = products.length > 0 ? products : extractProductsFromConversation();
		currentCheckoutStep = 0;
		
		// Reset customer data
		chatOrder.customer = {
			email: null,
			phone: null,
			first_name: null,
			last_name: null,
			address_1: null,
			city: null,
			postcode: null,
			country: chatOrder.customer.country || 'SI'
		};

		if ( chatOrder.products.length === 0 ) {
			addMessage( 'assistant', 'I need to know which products you\'d like to order. Could you please tell me?' );
			mode = 'chat'; // Go back to chat mode
			return;
		}

		// Ask first question
		const firstStep = checkoutSteps[0];
		addMessage( 'assistant', firstStep.question );
		messages.push( { role: 'assistant', content: firstStep.question } );
		saveCheckoutData();
	}

	// Handle checkout step
	function handleCheckoutStep( userInput ) {
		if ( currentCheckoutStep >= checkoutSteps.length ) {
			// All steps completed, create order
			createOrder();
			return;
		}

		const step = checkoutSteps[currentCheckoutStep];
		const value = userInput.trim();

		// Validate input
		if ( ! step.validator( value ) ) {
			addMessage( 'assistant', 'Please provide a valid ' + step.field.replace( '_', ' ' ) + '. ' + step.question );
			return;
		}

		// Store value
		chatOrder.customer[step.field] = value;
		saveCheckoutData();

		// Move to next step
		currentCheckoutStep++;

		if ( currentCheckoutStep < checkoutSteps.length ) {
			// Ask next question
			const nextStep = checkoutSteps[currentCheckoutStep];
			addMessage( 'assistant', nextStep.question );
			messages.push( { role: 'assistant', content: nextStep.question } );
		} else {
			// All fields collected, confirm and create order
			confirmAndCreateOrder();
		}
	}

	// Confirm order before creating
	function confirmAndCreateOrder() {
		const productNames = chatOrder.products.map( function( p ) {
			return 'Product ID ' + p.id + ' (Qty: ' + p.qty + ')';
		}).join( ', ' );

		const confirmMessage = 'Perfect! I have all the information:\n\n' +
			'Products: ' + productNames + '\n' +
			'Email: ' + chatOrder.customer.email + '\n' +
			'Phone: ' + chatOrder.customer.phone + '\n' +
			'Name: ' + chatOrder.customer.first_name + ' ' + chatOrder.customer.last_name + '\n' +
			'Address: ' + chatOrder.customer.address_1 + ', ' + chatOrder.customer.city + ' ' + chatOrder.customer.postcode + '\n\n' +
			'Should I create your order now? (Yes/No)';

		addMessage( 'assistant', confirmMessage );
		messages.push( { role: 'assistant', content: confirmMessage } );
	}

	// Check if user confirms order
	function checkOrderConfirmation( message ) {
		const confirmKeywords = [ 'yes', 'yep', 'yeah', 'ok', 'okay', 'sure', 'create', 'proceed', 'go ahead' ];
		const lowerMessage = message.toLowerCase().trim();
		return confirmKeywords.some( function( keyword ) {
			return lowerMessage === keyword || lowerMessage.startsWith( keyword + ' ' );
		});
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

		// Handle checkout mode
		if ( mode === 'checkout' ) {
			// Check if user is confirming order
			if ( currentCheckoutStep >= checkoutSteps.length && checkOrderConfirmation( message ) ) {
				createOrder();
				return;
			} else if ( currentCheckoutStep >= checkoutSteps.length ) {
				// User said no or something else
				addMessage( 'assistant', 'No problem! If you change your mind, just let me know.' );
				mode = 'chat';
				resetCheckout();
				return;
			}

			// Handle checkout step
			handleCheckoutStep( message );
			return;
		}

		// Chat mode - check for order intent
		if ( checkOrderIntent( message ) ) {
			// Extract products from conversation
			const products = extractProductsFromConversation();
			startCheckout( products );
			return;
		}

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
				mode: mode,
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

					// Check if AI suggests starting checkout
					if ( data.suggest_checkout && data.products && data.products.length > 0 ) {
						const productIds = data.products.map( function( p ) {
							return { id: p.id, qty: 1 };
						});
						startCheckout( productIds );
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

	// Save checkout data to sessionStorage
	function saveCheckoutData() {
		try {
			sessionStorage.setItem( 'asa_checkout_data', JSON.stringify( chatOrder ) );
			sessionStorage.setItem( 'asa_checkout_step', currentCheckoutStep.toString() );
			sessionStorage.setItem( 'asa_checkout_mode', mode );
		} catch ( e ) {
			// Ignore if sessionStorage is not available
		}
	}

	// Load checkout data from sessionStorage
	function loadCheckoutData() {
		try {
			const savedData = sessionStorage.getItem( 'asa_checkout_data' );
			const savedStep = sessionStorage.getItem( 'asa_checkout_step' );
			const savedMode = sessionStorage.getItem( 'asa_checkout_mode' );

			if ( savedData ) {
				chatOrder = JSON.parse( savedData );
			}
			if ( savedStep ) {
				currentCheckoutStep = parseInt( savedStep, 10 );
			}
			if ( savedMode ) {
				mode = savedMode;
			}
		} catch ( e ) {
			// Ignore if sessionStorage is not available or data is invalid
		}
	}

	// Reset checkout
	function resetCheckout() {
		mode = 'chat';
		currentCheckoutStep = 0;
		chatOrder.products = [];
		chatOrder.customer = {
			email: null,
			phone: null,
			first_name: null,
			last_name: null,
			address_1: null,
			city: null,
			postcode: null,
			country: chatOrder.customer.country || 'SI'
		};
		saveCheckoutData();
	}

	// Create order
	function createOrder() {
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
				products: chatOrder.products,
				customer: chatOrder.customer,
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
					successMessage += 'You\'ll receive confirmation at ' + chatOrder.customer.email + '. Thank you for your order!';
					
					addMessage( 'assistant', successMessage );
					messages.push( { role: 'assistant', content: successMessage } );
					
					// Reset checkout
					resetCheckout();
					mode = 'chat';
				} else if ( data.code ) {
					// Error from server
					let errorMessage = data.message || asaChatData.i18n.orderError || 'Unable to create order. Please try again.';
					
					// Add more specific error messages
					if ( data.code === 'no_products' ) {
						errorMessage = 'No valid products could be added to the order. Please check the product IDs.';
					} else if ( data.code === 'invalid_email' ) {
						errorMessage = 'Invalid email address provided.';
					} else if ( data.code === 'invalid_phone' ) {
						errorMessage = 'Phone number is required.';
					} else if ( data.code === 'invalid_address' ) {
						errorMessage = 'Delivery address is required.';
					} else if ( data.code === 'woocommerce_missing' || data.code === 'woocommerce_not_initialized' ) {
						errorMessage = 'WooCommerce is not available. Please ensure WooCommerce is installed and active.';
					} else if ( data.code === 'order_calculation_failed' ) {
						errorMessage = 'Failed to calculate order totals. Please try again or contact support.';
					}
					
					console.error( 'Order creation error:', data );
					addMessage( 'assistant', errorMessage );
				} else {
					console.error( 'Unknown order creation error:', data );
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
