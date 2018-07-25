package com.zack6849.website.controllers;

import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

import java.security.Principal;

@Controller
public class ManagerController  {
    @GetMapping(path = "/manager")
    public String manager(Model model, Principal principal){
        model.addAttribute("pageTitle", "Upload Managment Interface");
        model.addAttribute("principal", principal);
        return "manager";
    }
}
