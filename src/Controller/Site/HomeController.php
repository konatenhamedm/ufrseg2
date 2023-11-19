<?php

namespace App\Controller\Site;

use App\DTO\InscriptionDTO;
use App\Entity\Employe;
use App\Entity\Etudiant;
use App\Entity\NiveauEtudiant;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurGroupe;
use App\Form\CiviliteType;
use App\Form\EtudiantType;
use App\Form\RegisterType;
use App\Form\UtilisateurInscriptionSimpleType;
use App\Form\UtilisateurInscriptionType;
use App\Form\UtilisateurType;
use App\Repository\EmployeRepository;
use App\Repository\EtudiantRepository;
use App\Repository\FiliereRepository;
use App\Repository\FonctionRepository;
use App\Repository\GroupeRepository;
use App\Repository\NiveauEtudiantRepository;
use App\Repository\NiveauRepository;
use App\Repository\PersonneRepository;
use App\Repository\UtilisateurGroupeRepository;
use App\Repository\UtilisateurRepository;
use App\Security\LoginFormAuthenticator;
use App\Service\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class HomeController extends AbstractController
{

    #[Route(path: '/', name: 'site_home')]
    public function index(Request $request, FiliereRepository $filiereRepository): Response
    {
        return $this->render('site/index.html.twig', [
            'filieres' => $filiereRepository->findAll(),
        ]);
    }




    #[Route('/inscription/{niveau}/{filiere}', name: 'site_inscription_niveau', methods: ['GET', 'POST'])]
    public function inscription(Request $request, GroupeRepository $groupeRepository, UtilisateurGroupeRepository $utilisateurGroupeRepository, FonctionRepository $fonctionRepository, $niveau, $filiere, NiveauRepository $niveauRepository, NiveauEtudiantRepository $niveauEtudiantRepository, UtilisateurRepository $utilisateurRepository, EtudiantRepository $etudiantRepository, FormError $formError, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        /*$dsn = $this->getParameter('mail_dsn');
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);*/
        //$ccs = [$this->getUser()->getEmploye()->getAdresseMail()];


        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurInscriptionType::class, $utilisateur, [
            'method' => 'POST',
            'action' => $this->generateUrl('site_inscription_niveau', ['niveau' => $niveau, 'filiere' => $filiere])
        ]);
        $form->handleRequest($request);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('site_home');



            if ($form->isValid()) {


                $etudiant = new Etudiant();
                // dd($form->get('niveau')->getData());
                ///$etudiant->setContact('788877');
                $etudiant->setNom($form->get('nom')->getData());
                $etudiant->setFonction($fonctionRepository->findOneBy(array('code' => 'ETD')));
                $etudiant->setFonction($fonctionRepository->findOneBy(array('code' => 'ETD')));
                $etudiant->setPrenom($form->get('prenoms')->getData());
                $etudiant->setEmail($form->get('email')->getData());
                /*$etudiant->setMatricule("ff");*/
                $etudiant->setDateNaissance($form->get('dateNaissance')->getData());
                $etudiantRepository->add($etudiant, true);
                $inscription  = new NiveauEtudiant();

                $inscription->setEtat('attente');
                $inscription->setDate(new \DateTime());
                $inscription->setEtudiant($etudiant);
                $inscription->setNiveau($niveauRepository->findOneBy(array('code' => $niveau)));
                $inscription->setFiliere($fonctionRepository->findOneBy(array('code' => $filiere)));

                $niveauEtudiantRepository->add($inscription, true);

                //$utilisateur->setEmploye($etudiant);
                $utilisateur->setPassword($userPasswordHasher->hashPassword($utilisateur, $form->get('plainPassword')->getData()));
                $utilisateur->setPersonne($etudiant);
                $utilisateur->addRole('ROLE_ETUDIANT');
                $utilisateurRepository->add($utilisateur, true);

                /*  $groupe = new UtilisateurGroupe();

                $groupe->setUtilisateur($utilisateur);
                $groupe->setGroupe($groupeRepository->findOneBy(array('libelle' => 'Etudiants')));
                $utilisateurGroupeRepository->add($groupe, true);*/


                $data = true;
                $message       = 'Opération effectuée avec succès,vous pouvez verifier votre compte gmail';
                $statut = 1;
                $this->addFlash('success', $message);
                //$mailer->send($email);

            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                if (!$isAjax) {
                    $this->addFlash('warning', $message);
                }
            }


            if ($isAjax) {
                return $this->json(compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }
        }

        return $this->render('site/pages/inscription.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
            'niveau' => $niveauRepository->findOneBy(array('code' => $niveau)),
            'filiere' => $niveauRepository->findOneBy(array('code' => $niveau))->getFiliere()
        ]);
    }



    #[Route(path: '/inscription', name: 'site_register')]
    public function inscription_login(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        LoginFormAuthenticator $loginFormAuthenticator,
        NiveauRepository $niveauRepository,
        NiveauEtudiantRepository $niveauEtudiantRepository,
        FormError $formError,
        UtilisateurGroupeRepository $utilisateurGroupeRepository,
        GroupeRepository $groupeRepository
    ): Response {
        $inscriptionDTO = new InscriptionDTO();
        $form = $this->createForm(RegisterType::class, $inscriptionDTO, [
            'action' => $this->generateUrl('site_register'),
            'method' => 'POST'
        ]);

        $form->handleRequest($request);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();
        $redirect = $this->generateUrl($loginFormAuthenticator::DEFAULT_ROUTE);
        $fullRedirect = false;
        if ($form->isSubmitted()) {
            $response = [];
            $fonction = $entityManager->getRepository(Fonction::class)->findOneByCode('ETD');
            if ($form->isValid()) {
                $etudiant = new Etudiant();
                $etudiant->setNom($inscriptionDTO->getNom());
                $etudiant->setPrenom($inscriptionDTO->getPrenom());
                $etudiant->setDateNaissance($inscriptionDTO->getDateNaissance());
                $etudiant->setCivilite($inscriptionDTO->getCivilite());
                $etudiant->setGenre($inscriptionDTO->getGenre());
                $etudiant->setLieuNaissance('');
                $etudiant->setEmail($inscriptionDTO->getEmail());
                $etudiant->setFonction($fonction);
                $entityManager->persist($etudiant);
                $utilisateur = new Utilisateur();
                $utilisateur->setPassword($userPasswordHasher->hashPassword($utilisateur, $inscriptionDTO->getPlainPassword()));
                $utilisateur->addRole('ROLE_ETUDIANT');
                $utilisateur->setPersonne($etudiant);
                $utilisateur->setUsername($inscriptionDTO->getUsername());

                $entityManager->persist($utilisateur);

                $inscription  = new NiveauEtudiant();

                $inscription->setEtat('EN ATTENTE DE VALIDATION');
                $inscription->setDate(new \DateTime());
                $inscription->setEtudiant($etudiant);
                $inscription->setNiveau($niveauRepository->findOneBy(array('id' => $form->get('niveau')->getData()->getId())));
                $inscription->setFiliere($niveauRepository->findOneBy(array('id' => $form->get('niveau')->getData()->getId()))->getFiliere());

                $niveauEtudiantRepository->add($inscription, true);

                $groupe = new UtilisateurGroupe();

                $groupe->setUtilisateur($utilisateur);
                $groupe->setGroupe($groupeRepository->findOneBy(array('libelle' => 'Etudiants')));
                $utilisateurGroupeRepository->add($groupe, true);

                $entityManager->flush();

                $fullRedirect = true;


                $userAuthenticator->authenticateUser(
                    $utilisateur,
                    $loginFormAuthenticator,
                    $request
                );
                $statut = 1;
                $message = 'Compte crée avec succès';
                $this->addFlash('success', 'Votre compte a été crée avec succès. Veuillez vous connecter pour continuer l\'opération');
            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                    $this->addFlash('warning', $message);
                }
            }



            if ($isAjax) {
                return $this->json(compact('statut', 'message', 'redirect', 'data', 'fullRedirect'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }
        }


        return $this->render('security/register.html.twig', [
            'form' => $form
        ]);
    }


    #[Route(path: '/site/information', name: 'site_information')]
    public function information(Request $request, UserInterface $user, PersonneRepository $personneRepository, FormError $formError): Response
    {
        $etudiant = $user->getPersonne();

        //dd($etudiant);

        $form = $this->createForm(EtudiantType::class, $etudiant, [
            'method' => 'POST',
            'action' => $this->generateUrl('site_information')
        ]);

        $data = null;
        $statutCode = Response::HTTP_OK;

        $isAjax = $request->isXmlHttpRequest();


        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $response = [];
            $redirect = $this->generateUrl('site_information');




            if ($form->isValid()) {

                $personneRepository->add($etudiant, true);
                //$entityManager->flush();

                $data = true;
                $message       = 'Opération effectuée avec succès';
                $statut = 1;
                $this->addFlash('success', $message);
            } else {
                $message = $formError->all($form);
                $statut = 0;
                $statutCode = 500;
                if (!$isAjax) {
                    $this->addFlash('warning', $message);
                }
            }

            if ($isAjax) {
                return $this->json(compact('statut', 'message', 'redirect', 'data'), $statutCode);
            } else {
                if ($statut == 1) {
                    return $this->redirect($redirect, Response::HTTP_OK);
                }
            }
        }

        return $this->render('site/informations.html.twig', [
            'etudiant' => $etudiant,
            'form' => $form->createView(),
        ]);

        //return $this->render('site/admin/pages/informations.html.twig');
    }
}
