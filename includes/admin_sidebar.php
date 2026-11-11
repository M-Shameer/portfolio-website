
<?php

$activePage = $activePage ?? '';

?>

<aside class="sidebar">

    <a href="dashboard.php" class="brand">
        MS<span>.</span>
        <small>PORTFOLIO CMS</small>
    </a>

    <p class="sidebar-label">WORKSPACE</p>

    <nav class="sidebar-nav">

        <a href="dashboard.php"
           class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
            <span>▦ Overview</span>
        </a>

        <a href="profile.php"
           class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
            <span>◎ Profile</span>
        </a>

        
<a href="skills.php"
   class="nav-link <?php echo $activePage === 'skills' ? 'active' : ''; ?>">
    <span>◇ Skills</span>
</a>


        
<a href="experience.php"
   class="nav-link <?php echo $activePage === 'experience' ? 'active' : ''; ?>">
    <span>▤ Experience</span>
</a>


        
<a href="education.php"
   class="nav-link <?php echo $activePage === 'education' ? 'active' : ''; ?>">
    <span>▣ Education</span>
</a>


        
<a href="certifications.php"
   class="nav-link <?php echo $activePage === 'certifications' ? 'active' : ''; ?>">
    <span>✧ Certifications</span>
</a>


        
<a href="projects.php"
   class="nav-link <?php echo $activePage === 'projects' ? 'active' : ''; ?>">
    <span>▧ Projects</span>
</a>


        
<a href="social_links.php"
   class="nav-link <?php echo $activePage === 'social_links' ? 'active' : ''; ?>">
    <span>↗ Social Links</span>
</a>

        
<a href="messages.php"
   class="nav-link <?php echo $activePage === 'messages' ? 'active' : ''; ?>">
    <span>✉ Messages</span>
</a>


        
<a href="settings.php"
   class="nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">
    <span>⚙ Settings</span>
</a>


    </nav>

    <div class="sidebar-bottom">
        Private Administration Area
    </div>

</aside>
