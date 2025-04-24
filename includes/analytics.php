<!-- Meta Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '123456789012345'); // Remplacer par votre ID Pixel Meta
    fbq('track', 'PageView');
    
    // Événements personnalisés pour le tracking de conversion
    document.addEventListener('DOMContentLoaded', function() {
        // Tracking des clics sur CTA
        document.querySelectorAll('.cta-button').forEach(button => {
            button.addEventListener('click', function() {
                fbq('track', 'Lead', {content_name: 'cta_click'});
            });
        });
    });
</script>
<noscript>
    <img height="1" width="1" style="display:none" 
         src="https://www.facebook.com/tr?id=123456789012345&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->

<!-- Google Analytics (GA4) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXXX'); // Remplacer par votre ID GA4
    
    // Configuration des événements personnalisés
    document.addEventListener('DOMContentLoaded', function() {
        // Tracking du scroll
        let sections = ['hero', 'target', 'story', 'offer', 'pricing', 'faq', 'booking'];
        let sectionsSeen = {};
        
        // Initialisation des sections vues
        sections.forEach(section => {
            sectionsSeen[section] = false;
        });
        
        // Observer pour le suivi de scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    let sectionId = entry.target.id;
                    
                    // Si la section n'a pas encore été vue
                    if (sectionId && sections.includes(sectionId) && !sectionsSeen[sectionId]) {
                        sectionsSeen[sectionId] = true;
                        
                        // Envoi de l'événement à GA4
                        gtag('event', 'section_view', {
                            'section_id': sectionId
                        });
                    }
                }
            });
        }, { threshold: 0.5 }); // 50% de la section visible
        
        // Observer chaque section
        sections.forEach(section => {
            const element = document.getElementById(section);
            if (element) {
                observer.observe(element);
            }
        });
    });
</script>
<!-- End Google Analytics -->