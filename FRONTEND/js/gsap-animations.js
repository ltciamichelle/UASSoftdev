document.addEventListener("DOMContentLoaded", () => {
  // Ensure GSAP and ScrollTrigger are loaded
  if (typeof gsap === 'undefined') {
    console.error("GSAP is not loaded. Please include the GSAP script in your HTML.");
    return;
  }
  
  gsap.registerPlugin(ScrollTrigger);

  // 1. Initial Reveal (Hero & Staggered grids)
  const revealElements = document.querySelectorAll('.gsap-reveal');
  
  // Make them visible for GSAP to animate from autoAlpha 0
  gsap.set(revealElements, { autoAlpha: 0, y: 50, filter: "blur(10px)" });

  ScrollTrigger.batch(".gsap-reveal", {
    onEnter: batch => gsap.to(batch, {
      autoAlpha: 1,
      y: 0,
      filter: "blur(0px)",
      duration: 1.2,
      stagger: 0.15,
      ease: "power3.out",
      overwrite: true
    }),
    start: "top 90%",
  });

  // 2. Magnetic Buttons
  const magneticButtons = document.querySelectorAll('.magnetic');
  
  magneticButtons.forEach(btn => {
    btn.addEventListener('mousemove', (e) => {
      const rect = btn.getBoundingClientRect();
      const h = rect.width / 2;
      const v = rect.height / 2;
      
      const x = e.clientX - rect.left - h;
      const y = e.clientY - rect.top - v;
      
      // Move button slightly
      gsap.to(btn, {
        x: x * 0.3,
        y: y * 0.3,
        duration: 0.4,
        ease: "power2.out"
      });
      
      // Move icon wrapper even more for parallax effect inside button
      const iconWrapper = btn.querySelector('.btn-icon-wrapper');
      if (iconWrapper) {
        gsap.to(iconWrapper, {
          x: x * 0.5,
          y: y * 0.5,
          duration: 0.4,
          ease: "power2.out"
        });
      }
    });

    btn.addEventListener('mouseleave', () => {
      // Reset position
      gsap.to(btn, {
        x: 0,
        y: 0,
        duration: 0.7,
        ease: "elastic.out(1, 0.3)"
      });
      
      const iconWrapper = btn.querySelector('.btn-icon-wrapper');
      if (iconWrapper) {
        gsap.to(iconWrapper, {
          x: 0,
          y: 0,
          duration: 0.7,
          ease: "elastic.out(1, 0.3)"
        });
      }
    });
  });

  // Auto-append circular icon wrapper to all primary and ghost buttons
  const allBtns = document.querySelectorAll('.btn-primary, .btn-ghost');
  allBtns.forEach(btn => {
    if (!btn.querySelector('.btn-icon-wrapper')) {
      const wrapper = document.createElement('span');
      wrapper.className = 'btn-icon-wrapper';
      
      // Check if button already has a trailing icon
      const lastNode = btn.childNodes[btn.childNodes.length - 1];
      let trailingIcon = null;
      if (lastNode && lastNode.nodeType === Node.ELEMENT_NODE && lastNode.tagName === 'I') {
        trailingIcon = lastNode;
      } else if (lastNode && lastNode.nodeType === Node.TEXT_NODE && lastNode.textContent.trim() === '') {
        const prev = lastNode.previousSibling;
        if (prev && prev.nodeType === Node.ELEMENT_NODE && prev.tagName === 'I') {
          trailingIcon = prev;
        }
      }
      
      if (trailingIcon) {
        wrapper.appendChild(trailingIcon);
      } else {
        wrapper.innerHTML = `<i data-lucide="arrow-right"></i>`;
      }
      
      btn.appendChild(wrapper);
    }
  });

  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
});
