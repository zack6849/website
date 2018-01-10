package com.zack6849.website;

import com.zack6849.website.services.storage.StorageProperties;
import com.zack6849.website.services.storage.StorageService;
import com.zack6849.website.services.storage.exception.StorageException;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.context.properties.EnableConfigurationProperties;
import org.springframework.context.annotation.Bean;

import java.util.logging.Logger;

@SpringBootApplication
@EnableConfigurationProperties(StorageProperties.class)
public class WebsiteApplication {
	public static void main(String[] args) {
		SpringApplication.run(WebsiteApplication.class, args);
	}
	@Bean
	CommandLineRunner init(StorageService storageService){
		return (args -> {
			storageService.init();
		});
	}
}
