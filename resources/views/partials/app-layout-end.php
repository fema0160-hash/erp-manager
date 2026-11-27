    </main>
  </div>
</div>
<script>
function toggleAppSidebar(open) {
  const sidebar = document.getElementById('appSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!sidebar || !overlay) return;
  sidebar.classList.toggle('-translate-x-full', !open);
  overlay.classList.toggle('hidden', !open);
}
function updateAppClock() {
  const now = new Date();
  const date = document.getElementById('appLiveDate');
  const clock = document.getElementById('appLiveClock');
  if (date) date.textContent = now.toLocaleDateString('fr-FR');
  if (clock) clock.textContent = now.toLocaleTimeString('fr-FR');
}
updateAppClock();
setInterval(updateAppClock, 1000);
</script>
</body>
</html>
