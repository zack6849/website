package com.zack6849.website;

import com.zack6849.website.domain.Role;
import com.zack6849.website.domain.User;
import com.zack6849.website.repositories.RoleRepository;
import com.zack6849.website.repositories.UserRepository;
import com.zack6849.website.services.storage.StorageProperties;
import com.zack6849.website.services.storage.StorageService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.EnableAutoConfiguration;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.builder.SpringApplicationBuilder;
import org.springframework.boot.context.properties.EnableConfigurationProperties;
import org.springframework.boot.web.servlet.support.SpringBootServletInitializer;
import org.springframework.context.annotation.Bean;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;

import java.util.Date;

@SpringBootApplication
@EnableAutoConfiguration
@EnableConfigurationProperties(StorageProperties.class)
public class WebsiteApplication extends SpringBootServletInitializer {


    @Autowired
    public WebsiteApplication(UserRepository users, RoleRepository roles) {
        Role r = new Role();
        r.setName("Administrator");
        roles.save(r);
        User u = new User();
        u.setCreatedAt(new Date());
        u.setEmailaddress("zack@zack6849.com");
        u.setUsername("zack6849");
        u.setPassword("letmein");
        u.setEnabled(true);
        u.addRole(r);
        users.save(u);
    }

    public static void main(String[] args) {
		SpringApplication.run(WebsiteApplication.class, args);
	}

	@Bean
	CommandLineRunner init(StorageService storageService){
		return (args -> {
			storageService.init();
		});
	}

    @Bean
    public static BCryptPasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }

    @Override
    protected SpringApplicationBuilder configure(SpringApplicationBuilder builder) {
        return builder.sources(WebsiteApplication.class);
    }
}
