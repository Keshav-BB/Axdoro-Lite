/**
 * AXDORO Lean AI / FAQ Support Chatbot Script
 * Zero-cost client-side assistant with instant keyword matching & WhatsApp human escalation.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var toggleBtn   = document.getElementById('axdoro-bot-toggle');
        var chatWindow  = document.getElementById('axdoro-bot-window');
        var closeBtn    = document.getElementById('axdoro-bot-close-btn');
        var form        = document.getElementById('axdoro-bot-form');
        var input       = document.getElementById('axdoro-bot-input');
        var messagesBox = document.getElementById('axdoro-bot-messages');
        var humanLink   = document.getElementById('axdoro-human-link');

        if (!toggleBtn || !chatWindow) return;

        var data = window.axdoroBotData || {
            whatsappUrl: 'https://wa.me/918807304713',
            trackPageUrl: '/track-order/',
            shopPageUrl: '/shop/',
            bulkPageUrl: '/custom-bulk-orders/'
        };

        if (humanLink) {
            humanLink.href = data.whatsappUrl;
        }

        // Toggle chat window
        var isOpen = false;
        function toggleChat() {
            isOpen = !isOpen;
            if (isOpen) {
                chatWindow.style.display = 'flex';
                toggleBtn.querySelector('.axdoro-bot-icon-open').style.display = 'none';
                toggleBtn.querySelector('.axdoro-bot-icon-close').style.display = 'inline';
                if (input) input.focus();
            } else {
                chatWindow.style.display = 'none';
                toggleBtn.querySelector('.axdoro-bot-icon-open').style.display = 'inline';
                toggleBtn.querySelector('.axdoro-bot-icon-close').style.display = 'none';
            }
        }

        toggleBtn.addEventListener('click', toggleChat);
        if (closeBtn) closeBtn.addEventListener('click', toggleChat);

        // Curated FAQ Knowledge Base
        var knowledgeBase = [
            {
                keywords: ['size', 'fit', 'chart', 'measurement', 'small', 'medium', 'large', 'xl', 'xxl', 'oversized'],
                reply: 'AXDORO tees feature a relaxed, dropped-shoulder oversized fit crafted in dense 240 GSM cotton. If you prefer a classic regular look, we recommend sizing down one size. For a true streetwear drape, choose your regular size. Need exact chest measurements? Tap our WhatsApp link below!'
            },
            {
                keywords: ['fabric', 'gsm', 'material', 'quality', 'cotton', 'heavyweight', 'thick'],
                reply: 'Our core tees are built with substantial 240 GSM heavyweight cotton. It provides an architectural boxy drape that holds its shape, eliminates transparency, and resists collar stretching wash after wash.'
            },
            {
                keywords: ['shipping', 'delivery', 'dispatch', 'courier', 'days', 'time', 'free shipping'],
                reply: 'Orders are processed within 24–48 business hours. Delivery typically takes 3–5 working days across major Indian cities via our courier partners (Delhivery, BlueDart, Shadowfax). Enjoy Free Shipping on orders above ₹999!'
            },
            {
                keywords: ['track', 'where is my order', 'awb', 'status', 'tracking'],
                reply: 'You can check your order milestone anytime on our live tracking screen. Visit <a href="' + data.trackPageUrl + '" style="font-weight:600; text-decoration:underline;">Track Order</a> and enter your Order ID & phone number.'
            },
            {
                keywords: ['return', 'exchange', 'refund', 'policy', 'replacement', 'damaged'],
                reply: 'We offer a 7-day size exchange window from the date of delivery. Items must be unworn with original tags attached. To initiate an exchange, please message our support desk on WhatsApp.'
            },
            {
                keywords: ['bulk', 'custom', 'corporate', 'college', 'event', 'printing', 'wholesale', 'merch'],
                reply: 'Yes! We supply custom printed & blank 240 GSM heavyweight tees for colleges, corporate teams, and creative brands. Check our <a href="' + data.bulkPageUrl + '" style="font-weight:600; text-decoration:underline;">Custom & Bulk Orders</a> page or message our bulk desk on WhatsApp.'
            },
            {
                keywords: ['payment', 'upi', 'gpay', 'phonepe', 'paytm', 'cod', 'cash on delivery', 'utr'],
                reply: 'When you place an order on our site, you receive a unique Order ID and a direct UPI / WhatsApp payment link. You can complete payment instantly via GPay, PhonePe, or Paytm, and submit your UTR reference for instant verification.'
            }
        ];

        function findAnswer(query) {
            var cleanQuery = query.toLowerCase();
            for (var i = 0; i < knowledgeBase.length; i++) {
                var item = knowledgeBase[i];
                for (var k = 0; k < item.keywords.length; k++) {
                    if (cleanQuery.indexOf(item.keywords[k]) !== -1) {
                        return item.reply;
                    }
                }
            }
            return null;
        }

        function appendMessage(text, sender) {
            var msgDiv = document.createElement('div');
            msgDiv.className = 'axdoro-msg ' + (sender === 'user' ? 'axdoro-msg-user' : 'axdoro-msg-bot');
            var p = document.createElement('p');
            p.innerHTML = text;
            msgDiv.appendChild(p);
            messagesBox.appendChild(msgDiv);
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }

        function handleUserQuery(queryText) {
            if (!queryText) return;
            appendMessage(escapeHtml(queryText), 'user');

            // Simulate quick typing delay
            setTimeout(function() {
                var answer = findAnswer(queryText);
                if (answer) {
                    appendMessage(answer, 'bot');
                } else {
                    var fallback = 'I\'m not completely sure about that. Let me connect you directly with an AXDORO team member on WhatsApp!';
                    appendMessage(fallback + '<br><br><a href="' + data.whatsappUrl + '" target="_blank" style="display:inline-block; margin-top:4px; padding:4px 10px; background:#25D366; color:#fff; border-radius:4px; text-decoration:none; font-weight:600; font-size:12px;">💬 Chat on WhatsApp →</a>', 'bot');
                }
            }, 300);
        }

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var text = input.value.trim();
                if (text) {
                    input.value = '';
                    handleUserQuery(text);
                }
            });
        }

        // Quick chip clicks
        messagesBox.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('axdoro-chip')) {
                var query = e.target.getAttribute('data-query');
                handleUserQuery(query);
            }
        });
    });
})();
